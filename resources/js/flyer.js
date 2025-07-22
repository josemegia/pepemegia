import html2canvas from 'html2canvas';

document.addEventListener('DOMContentLoaded', () => {

    const captureBtn = document.getElementById('captureBtn');
    if (captureBtn) {
        captureBtn.addEventListener('click', function () {
            const captureElement = document.getElementById('flyer-to-capture');
            const buttonWrapper = document.getElementById('buttons-wrapper');
            const buttonTextSpan = document.getElementById('captureBtnText');
            const originalButtonText = buttonTextSpan ? buttonTextSpan.textContent : 'Capturar';

            if (buttonWrapper) buttonWrapper.style.display = 'none';
            if (buttonTextSpan) buttonTextSpan.textContent = 'Procesando...';
            this.disabled = true;

            setTimeout(() => {
                html2canvas(captureElement, {
                    scale: window.devicePixelRatio || 1,
                    useCORS: true,
                    backgroundColor: null
                }).then(canvas => {
                    canvas.toBlob(function (blob) {
                        if (navigator.clipboard && navigator.clipboard.write) {
                            navigator.clipboard.write([
                                new ClipboardItem({ 'image/png': blob })
                            ]).then(function () {
                                // console.log('Imagen copiada al portapapeles.');
                                if (buttonTextSpan) buttonTextSpan.textContent = '¡Copiado!';
                                // alert('¡Flyer copiado al portapapeles y descargado!');
                            }).catch(function (err) {
                                console.error('Error al copiar imagen al portapapeles: ', err);
                                if (buttonTextSpan) buttonTextSpan.textContent = 'Error copiar';
                                // alert('¡Flyer descargado! (No se pudo copiar al portapapeles automáticamente)');
                            });
                        } else {
                            console.warn('API de Clipboard.write no disponible.');
                            if (buttonTextSpan) buttonTextSpan.textContent = 'Descargado';
                            // alert('¡Flyer descargado! (Copiado no soportado)');
                        }
                    }, 'image/png');

                    const image = canvas.toDataURL('image/png');
                    const link = document.createElement('a');
                    const slug = captureElement.dataset.slug || 'flyer-invitacion';
                    link.download = `flyer-${slug}.png`;
                    link.href = image;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                }).catch(error => {
                    console.error('Error al capturar el flyer:', error);
                    if (buttonTextSpan) buttonTextSpan.textContent = 'Error';
                    // alert('Hubo un error al capturar el flyer.');
                }).finally(() => {
                    setTimeout(() => {
                        if (buttonWrapper) buttonWrapper.style.display = 'flex';
                        if (buttonTextSpan) buttonTextSpan.textContent = originalButtonText;
                        this.disabled = false;
                    }, 2000);
                });
            }, 100);
        });
    }

    const downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            const captureElement = document.getElementById('flyer-to-capture');
            const buttonWrapper = document.getElementById('buttons-wrapper');
            const slug = captureElement.dataset.slug || 'flyer-invitacion';

            if (buttonWrapper) buttonWrapper.style.display = 'none';

            setTimeout(() => {
                html2canvas(captureElement, {
                    scale: window.devicePixelRatio || 1,
                    useCORS: true,
                    backgroundColor: null
                }).then(canvas => {
                    const link = document.createElement('a');
                    link.download = `flyer-${slug}.png`;
                    link.href = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        if (buttonWrapper) buttonWrapper.style.display = 'flex';
                    }, 500);
                }).catch(error => {
                    console.error('Error al descargar el flyer:', error);
                    // alert('Hubo un error al descargar el flyer.');
                });
            }, 100);
        });
    }

    const copyLinkBtn = document.getElementById('copyLinkBtn');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function () {
            const linkInput = document.getElementById('sharedLinkInput');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(linkInput.value).then(function () {
                // console.log('✅ Enlace copiado al portapapeles: ' + linkInput.value);
                // alert('✅ Enlace copiado con éxito.');

                const originalIcon = copyLinkBtn.innerHTML;
                copyLinkBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>`;
                setTimeout(() => { copyLinkBtn.innerHTML = originalIcon; }, 2000);
            }).catch(function (err) {
                console.error('❌ Error al copiar el enlace: ', err);
                // alert('❌ No se pudo copiar el enlace. Inténtalo manualmente.');
            });

            const buttonWrapper = document.getElementById('buttons-wrapper');
            const confirmSharedUrl = buttonWrapper?.dataset.confirmSharedUrl;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            // console.log('🔄 confirmSharedUrl:', confirmSharedUrl);
            // console.log('🔄 CSRF token:', csrfToken);

            if (!sessionStorage.getItem('flyer_confirmed_shared') && confirmSharedUrl) {
                // alert('ℹ️ Enviando POST para marcar como compartido…');

                fetch(confirmSharedUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                }).then(res => {
                    if (res.ok) {
                        // console.log('✅ Flyer confirmado como compartido.');
                        // alert('✅ Confirmación enviada correctamente.');

                        sessionStorage.setItem('flyer_confirmed_shared', 'true');

                        const newFlyerButtonContainer = document.getElementById('new-flyer-button-container');
                        if (newFlyerButtonContainer) {
                            // console.log('✅ Mostrando botón nuevo flyer.');
                            newFlyerButtonContainer.style.display = 'flex';
                            // alert('✅ ¡Botón nuevo flyer mostrado!');
                        } else {
                            // alert('⚠️ No se encontró el div #new-flyer-button-container');
                        }

                    } else {
                        console.error('❌ Error HTTP al confirmar compartido:', res.status);
                        // alert('❌ Error HTTP al marcar como compartido: ' + res.status);
                    }
                }).catch(error => {
                    console.error('❌ Error en fetch confirmShared:', error);
                    // alert('❌ Error al enviar confirmación.');
                });
            } else {
                // console.log('ℹ️ Ya se confirmó en esta sesión o falta la URL.');
            }
        });
    }


});
