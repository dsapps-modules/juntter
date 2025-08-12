@extends('templates.dashboard-template')

@section('title', 'Editar Perfil')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Configurações', 'icon' => 'fas fa-cogs', 'url' => '#'],
        ['label' => 'Perfil', 'icon' => 'fas fa-user', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-user me-2 text-primary"></i>Editar Perfil
                        </h3>
                        <p class="text-muted mb-0">Atualize suas informações pessoais</p>
                    </div>
                </div>

                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Perfil atualizado com sucesso!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label fw-bold">
                                <i class="fas fa-user me-1 text-primary"></i>Nome Completo
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small id="name-hint" class="form-text"></small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-1 text-primary"></i>Email
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small id="email-hint" class="form-text"></small>
                            
                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="mt-2">
                                    <p class="text-sm text-warning">
                                        Seu endereço de email não foi verificado.
                                        <button form="send-verification" class="btn btn-link p-0 text-warning text-decoration-underline">
                                            Clique aqui para reenviar o email de verificação.
                                        </button>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nivel_acesso" class="form-label fw-bold">
                                <i class="fas fa-shield-alt me-1 text-primary"></i>Nível de Acesso
                            </label>
                            <input type="text" 
                                   class="form-control readonly-field" 
                                   value="{{ ucfirst($user->nivel_acesso) }}" 
                                   readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="created_at" class="form-label fw-bold">
                                <i class="fas fa-calendar me-1 text-primary"></i>Membro Desde
                            </label>
                            <input type="text" 
                                   class="form-control readonly-field" 
                                   value="{{ $user->created_at->format('d/m/Y') }}" 
                                   readonly>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('profile.password') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-key mr-2"></i>Alterar Senha
                        </a>
                        <button type="submit" id="saveProfileBtn" class="btn btn-warning text-white" disabled>
                            <i class="fas fa-save mr-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4 text-center">
                <div class="user-avatar-large mb-3">
                    <i class="fas fa-user-circle" style="font-size: 4rem; color: var(--primary-color);"></i>
                </div>
                <h5 class="fw-bold">{{ $user->name }}</h5>
                <p class="text-muted">{{ ucfirst($user->nivel_acesso) }}</p>
                <hr>
                <div class="text-start">
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        <small>{{ $user->email }}</small>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2 text-primary"></i>
                        <small>Membro desde {{ $user->created_at->format('M Y') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>
@endif

<style>
.readonly-field {
    background-color: #f8f9fa !important;
    border-color: #6c757d !important;
    color: #495057 !important;
    cursor: not-allowed;
}

.readonly-field:focus {
    box-shadow: none !important;
    border-color: #6c757d !important;
}

 

</style>
@push('scripts')
<script>
$(function(){
  const emailInput = $('#email');
  const nameInput = $('#name');
  const btn = $('#saveProfileBtn');
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  function validate(){
    const nameVal = (nameInput.val() || '').trim();
    const emailVal = (emailInput.val() || '').trim();
    const validName = nameVal.length >= 2;
    const validEmail = emailRegex.test(emailVal);

    // Mensagens de ajuda
    const $nameHint = $('#name-hint');
    const $emailHint = $('#email-hint');
    $nameHint.text(''); $emailHint.text('');
    $nameHint.removeClass('text-danger text-success');
    $emailHint.removeClass('text-danger text-success');

    if (!validName) {
      $nameHint.addClass('text-danger').text('Informe pelo menos 2 caracteres.');
    }
    if (emailVal.length === 0) {
      $emailHint.addClass('text-danger').text('Informe seu e-mail.');
    } else if (!validEmail) {
      $emailHint.addClass('text-danger').text('Formato de e-mail inválido.');
    }

    btn.prop('disabled', !(validEmail && validName));
  }

  emailInput.on('input', function(){
    const ok = emailRegex.test(this.value);
    $(this).toggleClass('is-invalid', !ok).toggleClass('is-valid', ok);
    validate();
  });
  nameInput.on('input', function(){
    const ok = (this.value || '').trim().length >= 2;
    $(this).toggleClass('is-invalid', !ok).toggleClass('is-valid', ok);
    validate();
  });

  validate();
});
</script>
@endpush
@endsection
