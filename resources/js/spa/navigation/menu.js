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
                { key: 'cobranca.unica', path: '/cobranca', label: 'Histórico', icon: 'unica' },
                { key: 'cobranca.pix', path: '/cobranca/pix', label: 'Pix', icon: 'pix' },
                { key: 'cobranca.cartao-credito', path: '/cobranca/cartao-credito', label: 'Cartão de Crédito', icon: 'cartao' },
                { key: 'cobranca.boleto', path: '/cobranca/boleto', label: 'Boleto', icon: 'boleto' },
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
                { key: 'cobranca.saldo', path: '/cobranca', label: 'Saldo e Extrato', icon: 'saldo' },
                { key: 'cobranca.simular', path: '/cobranca', label: 'Simular Transação', icon: 'simular' },
            ],
        },
        {
            label: 'Cobrança',
            children: [
                { key: 'cobranca.unica', path: '/cobranca', label: 'Histórico', icon: 'unica' },
                { key: 'cobranca.pix', path: '/cobranca/pix', label: 'Pix', icon: 'pix' },
                { key: 'cobranca.cartao-credito', path: '/cobranca/cartao-credito', label: 'Cartão de Crédito', icon: 'cartao' },
                { key: 'cobranca.boleto', path: '/cobranca/boleto', label: 'Boleto', icon: 'boleto' },
            ],
        },
    ],
};

export const sharedNavigationItems = [
    { key: 'cobranca.planos', path: '/cobranca/planos', label: 'Plano Contratado', icon: 'planos' },
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
            if (item.type === 'submenu' || item.type === 'group') {
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
            if (item.type === 'submenu' || item.type === 'group') {
                walk(item.children);
                return;
            }

            map.set(item.key, item.path);
        });
    };

    sections.forEach((section) => walk(section.children));

    return map;
}

