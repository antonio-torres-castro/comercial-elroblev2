        // Validación y envío
        document.addEventListener('DOMContentLoaded', function() {

            // Mostrar/ocultar campos de nueva tarea
            const tareaSelect = document.getElementById('tarea_id');
            const nuevaTareaFields = document.getElementById('nueva-tarea-fields');
            const nuevaTareaNombre = document.getElementById('nueva_tarea_nombre');

            const choices = new Choices(tareaSelect, {
                                        shouldSort: false,
                                        searchPlaceholderValue: "Buscar tarea...",
                                        itemSelectText: "",
                                        searchFields: ['label', 'value'],
                                        placeholder: true,
                                        allowHTML: true
                                        });


            tareaSelect.addEventListener('change', function() {
                if (this.value === 'nueva') {
                    nuevaTareaFields.style.display = 'block';
                    nuevaTareaNombre.setAttribute('required', 'required');
                } else {
                    nuevaTareaFields.style.display = 'none';
                    nuevaTareaNombre.removeAttribute('required');
                }
            });
            tareaSelect.dispatchEvent(new Event('change'));


            const form = document.getElementById('createTaskForm');
            const formData = new FormData(form);
            const createBtn = document.getElementById('createBtn');

            const fechaInicioMasivo = document.getElementById('fecha_inicio_masivo');
            const fechaFinMasivo = document.getElementById('fecha_fin_masivo');
            const fechaEspecificaInicio = document.getElementById('fecha_especifica_inicio');
            const fechaEspecificaFin = document.getElementById('fecha_especifica_fin');
            const fechaInicioRango = document.getElementById('fecha_inicio_rango');
            const fechaFinRango = document.getElementById('fecha_fin_rango');

            function validateDates(fechaInicio, fechaFin) {
                if (fechaInicio.value && fechaFin.value) {
                    const inicio = new Date(fechaInicio.value);
                    const fin = new Date(fechaFin.value);
                    if (fin < inicio) {
                        fechaFin.setCustomValidity('Fecha fin menor que fecha de inicio');
                        return false;
                    } else {
                        fechaFin.setCustomValidity('');
                    }
                }
                return true;
            }

            function validateDatesGetway() {
                var i, iors, tipoOcurrencia, retorno;
                tipoOcurrencia = formData.get('optionOcurrencia');
                if (tipoOcurrencia == '1') {
                    retorno = validateDates(fechaInicioMasivo, fechaFinMasivo);
                }
                if (tipoOcurrencia == '2') {
                    retorno = validateDates(fechaEspecificaInicio, fechaEspecificaFin);
                }
                if (tipoOcurrencia == '3') {
                    retorno = validateDates(fechaInicioRango, fechaFinRango);
                }
                return retorno;
            }

            ///Fecha recurrente
            fechaInicioMasivo.addEventListener('change', () => {
                fechaFinMasivo.min = fechaInicioMasivo.value;
                validateDates(fechaInicioMasivo, fechaFinMasivo);
            });
            fechaFinMasivo.addEventListener('change', () => {
                validateDates(fechaInicioMasivo, fechaFinMasivo);
            });
            ///Fecha Especifica
            fechaEspecificaInicio.addEventListener('change', () => {
                fechaEspecificaFin.min = fechaEspecificaInicio.value;
                validateDates(fechaEspecificaInicio, fechaEspecificaFin);
            });
            fechaEspecificaFin.addEventListener('change', () => {
                validateDates(fechaEspecificaInicio, fechaEspecificaFin);
            });
            ///Rango Fechas
            fechaInicioRango.addEventListener('change', () => {
                fechaFinRango.min = fechaInicioRango.value;
                validateDates(fechaInicioRango, fechaFinRango);
            });
            fechaFinRango.addEventListener('change', () => {
                validateDates(fechaInicioRango, fechaFinRango);
            });
            // Envío del formulario
            form.addEventListener('submit', function(e) {
                if (!validateDatesGetway()) {
                    e.preventDefault();
                    alert('Corrige las fechas antes de enviar.');
                    return;
                }

                if (!confirm('¿Deseas crear esta tarea?')) {
                    e.preventDefault();
                    return;
                }

                createBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Enviando...';
                createBtn.disabled = true;
            });
        });

        function selOpt(evt, nameIor) {
            var i, tablinks;
            tabpane = document.getElementsByName("tabpane");
            for (i = 0; i < tabpane.length; i++) {
                tabpane[i].style.display = "none";
                tabpane[i].className = tabpane[i].className.replace(" show active", "");
            }
            tablinks = document.getElementsByName("button-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }

            document.getElementById(nameIor + '-tab').className += " active";

            document.getElementById(nameIor).style.display = "";
            document.getElementById(nameIor).className += " show active";
        }

        function openTab(evt, nameTab) {
            var i, iors;
            iors = document.getElementsByName("optionOcurrencia");
            for (i = 0; i < iors.length; i++) {
                iors[i].checked = false;
                if (iors[i].id == 'ior' + nameTab) {
                    iors[i].checked = true;
                }
            }
            evt.currentTarget.className.replace(" active", "");
            evt.currentTarget.className += " active";

            document.getElementById(nameTab.toLowerCase()).style.display = "";
            document.getElementById(nameTab.toLowerCase()).className.replace(" show active", "");
            document.getElementById(nameTab.toLowerCase()).className += " show active";
        }