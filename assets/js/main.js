/**
 * FLUXO — Sistema de Gestão Doméstica
 * Interações de Interface, Modais, Validações e UX
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Drawer
    const mobileToggle = document.getElementById('mobileNavToggle');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (mobileToggle && sidebar && overlay) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // 2. Fechamento de Alertas Flash
    document.querySelectorAll('.btn-close-flash').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const banner = e.target.closest('.flash-banner');
            if (banner) {
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-10px)';
                setTimeout(() => banner.remove(), 250);
            }
        });
    });

    // Auto-dismiss após 6 segundos para alertas de sucesso
    setTimeout(() => {
        document.querySelectorAll('.flash-success').forEach(banner => {
            banner.style.transition = 'all 0.4s ease';
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-10px)';
            setTimeout(() => banner.remove(), 400);
        });
    }, 6000);

    // 3. Troca Dinâmica do Mês no Topbar
    const monthSelect = document.getElementById('topbarMonthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', () => {
            monthSelect.closest('form').submit();
        });
    }

    // 4. Confirmação Segura para Exclusões
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const itemName = btn.getAttribute('data-item-name') || 'este registro';
            const confirmed = confirm(`Tem certeza que deseja excluir "${itemName}"?\nEsta ação não poderá ser desfeita.`);
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });

    // 5. Feedback em tempo real ao preencher Valor e Limite da Conta
    const inputValor = document.getElementById('contaValor');
    const inputLimite = document.getElementById('contaLimite');
    const limitPreview = document.getElementById('contaLimitPreview');

    function updateLimitPreview() {
        if (!inputValor || !inputLimite || !limitPreview) return;
        const val = parseFloat(inputValor.value.replace(',', '.')) || 0;
        const lim = parseFloat(inputLimite.value.replace(',', '.')) || 0;

        if (lim > 0 && val > 0) {
            const pct = Math.round((val / lim) * 100);
            limitPreview.style.display = 'block';
            if (val > lim) {
                limitPreview.innerHTML = `⚠️ <strong style="color: var(--danger)">${pct}% do limite</strong> (ultrapassará em R$ ${(val - lim).toFixed(2).replace('.', ',')})`;
            } else if (pct >= 80) {
                limitPreview.innerHTML = `⚡ <strong style="color: var(--warning)">${pct}% do limite</strong> (próximo do teto)`;
            } else {
                limitPreview.innerHTML = `✓ <strong style="color: var(--success)">${pct}% do limite</strong> (confortável)`;
            }
        } else {
            limitPreview.style.display = 'none';
        }
    }

    if (inputValor && inputLimite) {
        inputValor.addEventListener('input', updateLimitPreview);
        inputLimite.addEventListener('input', updateLimitPreview);
        updateLimitPreview();
    }

    // 6. Olho para Mostrar / Ocultar Senha (Login e Cadastro)
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = '🙈';
                    btn.setAttribute('title', 'Ocultar senha');
                    btn.setAttribute('aria-label', 'Ocultar senha');
                } else {
                    input.type = 'password';
                    btn.textContent = '👁️';
                    btn.setAttribute('title', 'Mostrar senha');
                    btn.setAttribute('aria-label', 'Mostrar senha');
                }
            }
        });
    });
});
