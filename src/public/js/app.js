// Máscara de telefone
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[data-mask="telefone"]').forEach(input => {
        input.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);

            if (v.length <= 10) {
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else {
                v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
            }

            this.value = v;
        });
    });
});