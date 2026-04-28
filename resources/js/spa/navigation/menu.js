export const navigationByRole = {
    super_admin: [
        {
            label: 'Home',
            children: [
                { key: 'home.dashboard', path: '/home', label: 'Dashboard', icon: 'dashboard' },
            ],
        },
        {
            label: 'Administração',
            children: [
                { key: 'admin.estabelecimentos', path: '/estabelecimentos', label: 'Estabelecimentos', icon: 'estabelecimentos' },
            ],
        },
        {
            label: 'Vendedores',
            children: [
                { key: 'vendedores.estabelecimentos', path: '/vendedores', label: 'Estabelecimentos', icon: 'estabelecimentos' },
                { key: 'vendedores.faturamento', path: '/vendedores/faturamento', label: 'Faturamento', icon: 'faturamento' },
                { key: 'vendedores.acesso', path: '/vendedores/acesso', label: 'Acesso', icon: 'acesso' },
            ],
        },
        {
            label: 'Cobrança',
            children: [
                { key: 'cobranca.unica', path: '/cobranca', label: 'Cobrança Única', icon: 'unica' },
                {
                    type: 'submenu',
                    key: 'cobranca.links',
                    path: '/links-pagamento',
                    label: 'Links de Pagamento',
                    icon: 'links',
                    children: [
                        { key: 'links.cartao', path: '/links-pagamento', label: 'Cartão', icon: 'cartao' },
                        { key: 'links.pix', path: '/links-pagamento/novo?tipo=PIX', label: 'Pix', icon: 'pix' },
                        { key: 'links.boleto', path: '/links-pagamento/novo?tipo=BOLETO', label: 'Boleto', icon: 'boleto' },
                    ],
                },
                { key: 'cobranca.simular', path: '/cobranca', label: 'Simular Transação', icon: 'simular' },
                { key: 'cobranca.planos', path: '/cobranca', label: 'Planos de Cobrança', icon: 'planos' },
                { key: 'cobranca.saldo', path: '/cobranca', label: 'Saldo e Extrato', icon: 'saldo' },
            ],
        },
    ],
    admin: [
        {
            label: 'Home',
            children: [
                { key: 'home.dashboard', path: '/home', label: 'Dashboard', icon: 'dashboard' },
            ],
        },
        {
            label: 'Vendedores',
            children: [
                { key: 'vendedores.estabelecimentos', path: '/vendedores', label: 'Estabelecimentos', icon: 'estabelecimentos' },
                { key: 'vendedores.faturamento', path: '/vendedores/faturamento', label: 'Faturamento', icon: 'faturamento' },
                { key: 'vendedores.acesso', path: '/vendedores/acesso', label: 'Acesso', icon: 'acesso' },
            ],
        },
    ],
    vendedor: [
        {
            label: 'Home',
            children: [
                { key: 'home.dashboard', path: '/home', label: 'Dashboard', icon: 'dashboard' },
            ],
        },
        {
            label: 'Cobrança',
            children: [
                { key: 'cobranca.unica', path: '/cobranca', label: 'Cobrança Única', icon: 'unica' },
                {
                    type: 'submenu',
                    key: 'cobranca.links',
                    path: '/links-pagamento',
                    label: 'Links de Pagamento',
                    icon: 'links',
                    children: [
                        { key: 'links.cartao', path: '/links-pagamento', label: 'Cartão', icon: 'cartao' },
                        { key: 'links.pix', path: '/links-pagamento/novo?tipo=PIX', label: 'Pix', icon: 'pix' },
                        { key: 'links.boleto', path: '/links-pagamento/novo?tipo=BOLETO', label: 'Boleto', icon: 'boleto' },
                    ],
                },
                { key: 'cobranca.simular', path: '/cobranca', label: 'Simular Transação', icon: 'simular' },
                { key: 'cobranca.planos', path: '/cobranca', label: 'Planos de Cobrança', icon: 'planos' },
                { key: 'cobranca.saldo', path: '/cobranca', label: 'Saldo e Extrato', icon: 'saldo' },
            ],
        },
    ],
};

export const sharedNavigationItems = [
    { key: 'perfil.configuracoes', path: '/perfil', label: 'Perfil', icon: 'perfil' },
];

export function buildNavigationSections(role) {
    return navigationByRole[role] ?? [];
}

export function findNavigationItem(sections, path) {
    const normalizedPath = path ?? '';
    let bestMatch = null;

    const considerItem = (item) => {
        if (!item.path || !normalizedPath.startsWith(item.path)) {
            return;
        }

        if (!bestMatch || item.path.length > bestMatch.path.length) {
            bestMatch = item;
        }
    };

    const walk = (items) => {
        items.forEach((item) => {
            if (item.type === 'submenu') {
                considerItem(item);
                walk(item.children);
                return;
            }

            considerItem(item);
        });
    };

    sections.forEach((section) => walk(section.children));

    return bestMatch;
}

export function buildKeyPathMap(sections) {
    const map = new Map();

    const walk = (items) => {
        items.forEach((item) => {
            if (item.type === 'submenu') {
                map.set(item.key, item.path);
                walk(item.children);
                return;
            }

            map.set(item.key, item.path);
        });
    };

    sections.forEach((section) => walk(section.children));

    return map;
}
