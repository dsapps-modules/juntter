import { Button, Card, Space, Typography } from 'antd';
import { Component } from 'react';

export default class SpaErrorBoundary extends Component {
    constructor(props) {
        super(props);

        this.state = {
            hasError: false,
            errorMessage: '',
        };
    }

    static getDerivedStateFromError(error) {
        return {
            hasError: true,
            errorMessage: error instanceof Error ? error.message : 'Ocorreu um erro inesperado na SPA.',
        };
    }

    componentDidCatch(error) {
        this.setState({
            hasError: true,
            errorMessage: error instanceof Error ? error.message : 'Ocorreu um erro inesperado na SPA.',
        });
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="spa-error-boundary">
                    <Card className="spa-error-boundary-card">
                        <Space direction="vertical" size={16}>
                            <Typography.Text className="spa-brand-kicker">Juntter SPA</Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                Não foi possível carregar a interface.
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                {this.state.errorMessage}
                            </Typography.Paragraph>
                            <Button type="primary" onClick={() => window.location.reload()}>
                                Recarregar
                            </Button>
                        </Space>
                    </Card>
                </div>
            );
        }

        return this.props.children;
    }
}
