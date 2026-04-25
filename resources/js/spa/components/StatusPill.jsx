import { Tag } from 'antd';

const statusMap = {
    Ativo: { color: 'gold', label: 'Ativo' },
    'Em análise': { color: 'default', label: 'Em análise' },
    Inativo: { color: 'volcano', label: 'Inativo' },
    Bloqueado: { color: 'red', label: 'Bloqueado' },
};

export default function StatusPill({ status }) {
    const current = statusMap[status] ?? statusMap['Em análise'];

    return <Tag color={current.color}>{current.label}</Tag>;
}
