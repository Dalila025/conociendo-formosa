// Buscamos todos los puntos del mapa
const puntos = document.querySelectorAll(".punto");

// Buscamos el panel de información
const panel = document.getElementById("info-panel");

// Elementos donde vamos a mostrar la información
const tituloLugar = document.getElementById("titulo-lugar");
const descripcionLugar = document.getElementById("descripcion-lugar");
const datoLugar = document.getElementById("dato-lugar");
const imagenLugar = document.getElementById("imagen-lugar");

// Botón para cerrar
const cerrarPanel = document.getElementById("cerrar-panel");


// Información de los lugares
const lugares = {

    banado: {

        titulo: "Bañado La Estrella",

        imagen: "img/banado-la-estrella.jpg",

        descripcion:
            "Es uno de los grandes humedales de Formosa y se destaca por su gran diversidad de flora y fauna. Su paisaje cambia según la época del año.",

        dato:
            "El Bañado La Estrella fue reconocido como una de las 7 Maravillas Naturales de Argentina."
    }

};


// Detectamos cuando se hace clic en un punto
puntos.forEach(function(punto) {

    punto.addEventListener("click", function() {

        // Obtenemos el lugar del punto
        const lugarSeleccionado = punto.dataset.lugar;

        // Buscamos la información correspondiente
        const informacion = lugares[lugarSeleccionado];


        // Mostramos la información
        tituloLugar.textContent = informacion.titulo;

        descripcionLugar.textContent = informacion.descripcion;

        datoLugar.textContent = informacion.dato;

        imagenLugar.src = informacion.imagen;


        // Mostramos el panel
        panel.classList.add("mostrar");

    });

});


// Cerramos el panel
cerrarPanel.addEventListener("click", function() {

    panel.classList.remove("mostrar");

});
const opciones = document.querySelectorAll(".opcion");

const resultadoPregunta = document.getElementById("resultado-pregunta");


opciones.forEach(function(opcion) {

    opcion.addEventListener("click", function() {

        const esCorrecta = opcion.dataset.correcta === "true";


        if (esCorrecta) {

            resultadoPregunta.textContent =
                "✅ ¡Correcto! El Bañado La Estrella es un importante humedal de Formosa.";

        } else {

            resultadoPregunta.textContent =
                "❌ No es correcto. Intentá nuevamente.";

        }

    });

});