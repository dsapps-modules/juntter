<div class="modal-overlay modal-orienta-saldo" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <img src="https://checkout.juntter.com.br/img/logo/juntter_webp_640_174.webp" alt="Juntter Logo">
        </div>

        <div class="modal-body">
            <p class="info-text">
                Você será redirecionado para a nossa página de consulta do seu saldo na Juntter daqui a pouco. Para o
                seu primeiro acesso, é essencial seguir as seguintes etapas:
            </p>

            <div class="steps-list">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-text">Na próxima página da Juntter, você precisa clicar em "Esqueceu a senha?"
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-text">Depois digite o seu e-mail que utilizou para cadastrar conosco na Juntter.
                    </div>
                </div>

                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-text">Em poucos segundos você receberá um link via e-mail (fique atento para ver se
                        não caiu no Spam ou Lixo Eletrônico!) para definir sua senha de acesso ao sistema de consulta de
                        saldo.</div>
                </div>

                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-text">Após definir sua senha, você terá acesso liberado ao sistema utilizando a
                        nova senha criada e o e-mail da Juntter.</div>
                </div>
            </div>

            <div class="alert-box">
                <i class="fas fa-lightbulb"></i>
                <span>Dica: Verifique sua pasta de Spam ou Lixo Eletrônico se não receber o e-mail dentro de alguns
                    minutos.</span>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-redirect" onclick="redirectToJuntter()">
                <i class="fas fa-arrow-right" style="margin-right: 8px;"></i>
                Clique aqui para ser redirecionado
            </button>
            <button class="btn-close" onclick="closeModal()">
                Fechar
            </button>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .modal-orienta-saldo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1055;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-orienta-saldo .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            animation: slideUp 0.4s ease-out;
            border-top: 5px solid var(--primary-color);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-orienta-saldo .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 30px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }

        .modal-header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .modal-orienta-saldo .modal-body {
            padding: 30px;
            max-height: 500px;
            overflow-y: auto;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        .info-text {
            color: #333;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .steps-list {
            margin: 20px 0;
        }

        .step-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .step-number {
            background: var(--primary-color);
            color: #333;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(255, 207, 0, 0.3);
        }

        .step-text {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            padding-top: 3px;
        }

        .modal-orienta-saldo .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-orienta-saldo .btn-redirect {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #333;
            padding: 12px 40px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 207, 0, 0.3);
        }

        .modal-orienta-saldo .btn-redirect:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 207, 0, 0.4);
        }

        .modal-orienta-saldo .btn-redirect:active {
            transform: translateY(0);
        }

        .modal-orienta-saldo .btn-close {
            background: #f0f0f0;
            color: #666;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .modal-orienta-saldo .btn-close:hover {
            background: #e0e0e0;
            color: #333;
        }

        .modal-orienta-saldo .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid var(--secondary-color);
            padding: 12px 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 13px;
            color: #856404;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .modal-orienta-saldo .alert-box i {
            color: var(--secondary-color);
            margin-top: 2px;
            flex-shrink: 0;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function redirectToJuntter() {
            alert('Você será redirecionado para a página de acesso da Juntter.');
            window.location.href = 'https://login.juntter.com.br/client/dashboard';
        }

        function closeModal() {
            document.querySelector('.modal-overlay').style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => {
                document.querySelector('.modal-overlay').style.display = 'none';
            }, 300);
        }
    </script>
@endpush
