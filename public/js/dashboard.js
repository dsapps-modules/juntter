/**
 * Dashboard JavaScript Functions
 * Usando jQuery para melhor compatibilidade
 */

$(document).ready(function() {
    
    // Inicializar animações dos cards
    initDashboardAnimations();
    
    // Inicializar contadores
    initCounters();
    
});

/**
 * Função para trocar abas do analytics
 */
function switchTab(tabName) {
    // Remove active class de todas as abas
    $('.tab-btn').removeClass('active');
    
    // Add active class na aba clicada
    $(event.target).addClass('active');
    
    console.log('Aba ativa:', tabName);
}

/**
 * Animações dos elementos do dashboard
 */
function initDashboardAnimations() {
    // Animar elementos com fade-in-up
    $('.fade-in-up').each(function(index) {
        const $element = $(this);
        const delay = $element.data('delay') || (index * 0.1);
        
        // Set initial state
        $element.css({
            'opacity': '0',
            'transform': 'translateY(30px)',
            'transition': 'all 0.6s ease'
        });
        
        // Trigger animation with delay
        setTimeout(function() {
            $element.css({
                'opacity': '1',
                'transform': 'translateY(0)'
            });
        }, delay * 1000);
    });
}

/**
 * Animação de contadores (count up)
 */
function initCounters() {
    $('.saldo-valor, .metric-value').each(function() {
        const $element = $(this);
        const text = $element.text();
        
        // Animação básica de fade in
        if (text.includes('R$') || !isNaN(parseInt(text))) {
            $element.css('opacity', '0').animate({'opacity': '1'}, 1000);
        }
    });
}

// Expor função globalmente para uso inline
window.switchTab = switchTab;