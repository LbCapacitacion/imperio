(function () {
    var MESES = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];

    function fechaJornada() {
        var partes = new Intl.DateTimeFormat("en-CA", {
            timeZone: "America/Mexico_City",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            hourCycle: "h23"
        }).formatToParts(new Date());
        var valor = {};
        partes.forEach(function (parte) {
            if (parte.type !== "literal") {
                valor[parte.type] = parte.value;
            }
        });
        var marca = new Date(Date.UTC(Number(valor.year), Number(valor.month) - 1, Number(valor.day)));
        if (Number(valor.hour) < 14) {
            marca.setUTCDate(marca.getUTCDate() - 1);
        }
        return marca.toISOString().slice(0, 10);
    }

    function escapar(texto) {
        return String(texto == null ? "" : texto).replace(/[&<>"']/g, function (caracter) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            }[caracter];
        });
    }

    function dinero(cantidad) {
        return Number(cantidad || 0).toLocaleString("es-MX", {
            style: "currency",
            currency: "MXN"
        });
    }

    function hora(valor) {
        if (!valor) {
            return "";
        }
        var texto = String(valor);
        if (texto.indexOf("T") !== -1) {
            var instante = new Date(texto);
            if (!isNaN(instante.getTime())) {
                return new Intl.DateTimeFormat("es-MX", {
                    timeZone: "America/Mexico_City",
                    day: "numeric",
                    month: "short",
                    hour: "2-digit",
                    minute: "2-digit",
                    hourCycle: "h23"
                }).format(instante);
            }
        }
        var fecha = texto.slice(0, 10).split("-");
        return Number(fecha[2]) + " " + MESES[Number(fecha[1]) - 1] + " " + texto.slice(11, 16);
    }

    function estadoDe(pedido) {
        var estado = Number(pedido.estado);
        if (estado === 2) {
            return { texto: "Cancelado", clase: "jornada-estado jornada-estado-cancelado" };
        }
        if (estado === 1) {
            return { texto: "Completado", clase: "jornada-estado jornada-estado-completo" };
        }
        return { texto: "Pendiente", clase: "jornada-estado jornada-estado-pendiente" };
    }

    async function consultar(ruta, opciones) {
        var token = localStorage.getItem("AUTH_TOKEN");
        var encabezados = {
            Accept: "application/json",
            "Content-Type": "application/json"
        };
        if (token) {
            encabezados.Authorization = "Bearer " + token;
        }
        var respuesta = await fetch(ruta, Object.assign({}, opciones, {
            headers: Object.assign(encabezados, opciones && opciones.headers),
            cache: "no-store"
        }));

        if (respuesta.status === 401) {
            localStorage.removeItem("AUTH_TOKEN");
            window.location.href = "/auth/login";
            throw new Error("Sesión expirada");
        }

        var datos = await respuesta.json().catch(function () {
            return {};
        });

        if (!respuesta.ok) {
            throw new Error(datos.message || datos.error || "No se pudo completar la acción");
        }

        return datos;
    }

    function montar(raiz) {
        raiz.dataset.ready = "1";
        raiz.innerHTML = ''
            + '<section class="jornada">'
            + '<a class="jornada-volver" href="/admin">Volver a ordenes</a>'
            + '<h1>Pedidos de la jornada</h1>'
            + '<p class="jornada-aviso">Un pedido cancelado se queda en esta lista y no entra al corte. El stock regresa al inventario.</p>'
            + '<div class="jornada-herramientas">'
            + '<label>Fecha <input id="jornada-fecha" type="date"></label>'
            + '<button id="jornada-actualizar" type="button">Actualizar</button>'
            + '</div>'
            + '<p id="jornada-rango" class="jornada-rango"></p>'
            + '<p id="jornada-resumen" class="jornada-resumen"></p>'
            + '<div id="jornada-lista"></div>'
            + '</section>';

        var fecha = raiz.querySelector("#jornada-fecha");
        fecha.value = fechaJornada();
        fecha.addEventListener("change", function () {
            cargar(raiz);
        });
        raiz.querySelector("#jornada-actualizar").addEventListener("click", function () {
            cargar(raiz);
        });
        raiz.addEventListener("click", function (evento) {
            var boton = evento.target.closest("[data-cancelar]");
            if (!boton || !raiz.contains(boton)) {
                return;
            }
            cancelar(raiz, boton);
        });
        cargar(raiz);
    }

    async function cargar(raiz) {
        var lista = raiz.querySelector("#jornada-lista");
        var rango = raiz.querySelector("#jornada-rango");
        var resumen = raiz.querySelector("#jornada-resumen");
        var fecha = raiz.querySelector("#jornada-fecha").value;

        lista.textContent = "Cargando pedidos...";
        rango.textContent = "";
        resumen.textContent = "";

        try {
            var datos = await consultar("/api/pedidos/jornada?date=" + encodeURIComponent(fecha));
            rango.textContent = "Del " + hora(datos.start) + " al " + hora(datos.end);
            dibujar(lista, resumen, datos.pedidos || []);
        } catch (error) {
            lista.textContent = error.message;
        }
    }

    function dibujar(lista, resumen, pedidos) {
        if (!pedidos.length) {
            lista.textContent = "No hay pedidos en esta jornada.";
            return;
        }

        var completados = pedidos.filter(function (pedido) { return Number(pedido.estado) === 1; });
        var cancelados = pedidos.filter(function (pedido) { return Number(pedido.estado) === 2; });
        var suma = function (grupo) {
            return grupo.reduce(function (total, pedido) { return total + Number(pedido.total || 0); }, 0);
        };

        resumen.textContent = "Completados: " + completados.length + " · " + dinero(suma(completados))
            + "    Cancelados: " + cancelados.length + " · " + dinero(suma(cancelados)) + ", fuera del corte";

        lista.innerHTML = pedidos.map(function (pedido) {
            var estado = estadoDe(pedido);
            var productos = (pedido.productos || []).map(function (producto) {
                var cantidad = producto.pivot && producto.pivot.cantidad ? producto.pivot.cantidad : 1;
                return "<li>" + escapar(producto.nombre) + " <span>x" + escapar(cantidad) + "</span></li>";
            }).join("");
            var cancelado = Number(pedido.estado) === 2;
            var accion = cancelado
                ? ""
                : '<button type="button" data-cancelar="' + escapar(pedido.id) + '" data-mesa="' + escapar(pedido.mesa || "sin mesa") + '">Cancelar</button>';

            return ''
                + '<article class="jornada-pedido' + (cancelado ? " jornada-pedido-cancelado" : "") + '">'
                + '<header>'
                + "<div><strong>" + escapar(pedido.mesa || "Sin mesa") + "</strong><span>Pedido " + escapar(pedido.id) + "</span></div>"
                + '<b class="' + estado.clase + '">' + estado.texto + "</b>"
                + "</header>"
                + "<p>" + escapar(pedido.user && pedido.user.name ? pedido.user.name : "Sin mesero") + " · " + hora(pedido.created_at) + "</p>"
                + "<ul>" + productos + "</ul>"
                + '<footer><strong>' + dinero(pedido.total) + "</strong>" + accion + "</footer>"
                + "</article>";
        }).join("");
    }

    async function cancelar(raiz, boton) {
        var mesa = boton.getAttribute("data-mesa");
        var aceptar = window.confirm("¿Cancelar el pedido de " + mesa + "? Se queda en la lista como cancelado y no entra al corte.");
        if (!aceptar) {
            return;
        }

        boton.disabled = true;
        try {
            await consultar("/api/pedidos/" + boton.getAttribute("data-cancelar") + "/cancelar", { method: "PATCH" });
            await cargar(raiz);
        } catch (error) {
            boton.disabled = false;
            window.alert(error.message);
        }
    }

    function vigilar() {
        var panel = document.getElementById("jornada-panel");
        if (window.location.pathname !== "/admin/jornada") {
            if (panel) {
                panel.remove();
            }
            return;
        }
        if (panel) {
            return;
        }
        panel = document.createElement("div");
        panel.id = "jornada-panel";
        document.body.appendChild(panel);
        montar(panel);
    }

    window.setInterval(vigilar, 400);
})();
