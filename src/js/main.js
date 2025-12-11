// Atualiza todos os contadores a cada segundo
function atualizarContadores() {
    document.querySelectorAll('.timer .countdown').forEach(el => {
        const fim = new Date(el.parentElement.dataset.end).getTime();
        const agora = new Date().getTime();
        const diff = Math.floor((fim - agora) / 1000);

        if (diff <= 0) {
            el.textContent = "Terminado";
            el.parentElement.parentElement.parentElement.classList.add('ended');
            return;
        }

        const dias = Math.floor(diff / 86400);
        const horas = Math.floor((diff % 86400) / 3600);
        const minutos = Math.floor((diff % 3600) / 60);
        const segundos = diff % 60;

        let texto = '';
        if (dias > 0) texto += dias + 'd ';
        texto += String(horas).padStart(2, '0') + ':';
        texto += String(minutos).padStart(2, '0') + ':';
        texto += String(segundos).padStart(2, '0');

        el.textContent = texto;
    });
}

// Atualiza a cada segundo
setInterval(atualizarContadores, 1000);
atualizarContadores(); // primeira execução