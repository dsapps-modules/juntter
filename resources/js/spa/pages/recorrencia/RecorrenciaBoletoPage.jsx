import RecorrenciaFormPage from './RecorrenciaFormPage';

export default function RecorrenciaBoletoPage() {
    return (
        <RecorrenciaFormPage
            paymentType="BOLETO"
            selectorPath="/recorrencia"
        />
    );
}
