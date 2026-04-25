import { Card, Typography } from 'antd';

export default function ComingSoonPage({ title, description }) {
    return (
        <Card className="spa-placeholder-card">
            <Typography.Text className="spa-placeholder-kicker">Em migração</Typography.Text>
            <Typography.Title level={2} className="spa-placeholder-title">
                {title}
            </Typography.Title>
            <Typography.Paragraph className="spa-placeholder-description">
                {description}
            </Typography.Paragraph>
            <Typography.Text type="secondary">
                Esta rota já está reservada para a versão React + API do módulo.
            </Typography.Text>
        </Card>
    );
}
