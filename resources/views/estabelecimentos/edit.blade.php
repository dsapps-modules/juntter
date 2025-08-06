@extends('templates.dashboard-template')

@section('title', 'Editar Estabelecimento')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Estabelecimentos', 'icon' => 'fas fa-building', 'url' => route('estabelecimentos.show', $estabelecimento['id'])],
        ['label' => 'Editar', 'icon' => 'fas fa-edit', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">Editar {{ $estabelecimento['name'] ?? 'Estabelecimento' }}</h3>
                        <p class="text-muted mb-0">Atualize as informações do estabelecimento</p>
                    </div>
                    <div>
                        <a href="{{ route('estabelecimentos.show', $estabelecimento['id']) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-2"></i>Visualizar
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
                
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('estabelecimentos.update', $estabelecimento['id']) }}">
                    @csrf
                    @method('PUT')
                    
                                         <div class="row">
                         <div class="col-md-6">
                             <h5 class="fw-bold mb-3">Informações Básicas</h5>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Tipo de Acesso *</label>
                                 <select name="access_type" class="form-select @error('access_type') is-invalid @enderror" required>
                                     <option value="ACQUIRER" {{ (old('access_type', $estabelecimento['access_type'] ?? '') == 'ACQUIRER') ? 'selected' : '' }}>ACQUIRER</option>
                                     <option value="BANKING" {{ (old('access_type', $estabelecimento['access_type'] ?? '') == 'BANKING') ? 'selected' : '' }}>BANKING</option>
                                 </select>
                                 @error('access_type')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Nome / Razão Social</label>
                                 <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" 
                                        value="{{ old('first_name', $estabelecimento['first_name'] ?? '') }}">
                                 @error('first_name')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Nome Fantasia / Sobrenome</label>
                                 <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" 
                                        value="{{ old('last_name', $estabelecimento['last_name'] ?? '') }}">
                                 @error('last_name')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Email *</label>
                                 <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                        value="{{ old('email', $estabelecimento['email'] ?? '') }}" required>
                                 @error('email')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Telefone *</label>
                                 <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" 
                                        value="{{ old('phone_number', $estabelecimento['phone_number'] ?? '') }}" required>
                                 @error('phone_number')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                         </div>
                         <div class="col-md-6">
                             <h5 class="fw-bold mb-3">Informações Empresariais</h5>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Tipo Societário *</label>
                                 <select name="format" class="form-select @error('format') is-invalid @enderror" required>
                                     <option value="SS" {{ (old('format', $estabelecimento['format'] ?? '') == 'SS') ? 'selected' : '' }}>SS - Sociedade Simples</option>
                                     <option value="SC" {{ (old('format', $estabelecimento['format'] ?? '') == 'SC') ? 'selected' : '' }}>SC - Sociedade Civil</option>
                                     <option value="SPE" {{ (old('format', $estabelecimento['format'] ?? '') == 'SPE') ? 'selected' : '' }}>SPE - Sociedade de Propósito Específico</option>
                                     <option value="LTDA" {{ (old('format', $estabelecimento['format'] ?? '') == 'LTDA') ? 'selected' : '' }}>LTDA - Sociedade Limitada</option>
                                     <option value="SA" {{ (old('format', $estabelecimento['format'] ?? '') == 'SA') ? 'selected' : '' }}>SA - Sociedade Anônima</option>
                                     <option value="ME" {{ (old('format', $estabelecimento['format'] ?? '') == 'ME') ? 'selected' : '' }}>ME - Microempresa</option>
                                     <option value="MEI" {{ (old('format', $estabelecimento['format'] ?? '') == 'MEI') ? 'selected' : '' }}>MEI - Microempreendedor Individual</option>
                                     <option value="EI" {{ (old('format', $estabelecimento['format'] ?? '') == 'EI') ? 'selected' : '' }}>EI - Empresário Individual</option>
                                     <option value="EIRELI" {{ (old('format', $estabelecimento['format'] ?? '') == 'EIRELI') ? 'selected' : '' }}>EIRELI - Empresa Individual de Responsabilidade Limitada</option>
                                     <option value="SLU" {{ (old('format', $estabelecimento['format'] ?? '') == 'SLU') ? 'selected' : '' }}>SLU - Sociedade Limitada Unipessoal</option>
                                     <option value="ESI" {{ (old('format', $estabelecimento['format'] ?? '') == 'ESI') ? 'selected' : '' }}>ESI - Empresa Simples de Inovação</option>
                                 </select>
                                 @error('format')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Receita Mensal (R$) *</label>
                                 <input type="number" name="revenue" class="form-control @error('revenue') is-invalid @enderror" 
                                        value="{{ old('revenue', $estabelecimento['revenue'] ?? '') }}" required>
                                 @error('revenue')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">GMV - Volume Bruto de Vendas</label>
                                 <input type="number" name="gmv" class="form-control @error('gmv') is-invalid @enderror" 
                                        value="{{ old('gmv', $estabelecimento['gmv'] ?? '') }}">
                                 @error('gmv')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                             <div class="mb-3">
                                 <label class="form-label fw-bold">Data de Nascimento/Fundação *</label>
                                 <input type="date" name="birthdate" class="form-control @error('birthdate') is-invalid @enderror" 
                                        value="{{ old('birthdate', $estabelecimento['birthdate'] ?? '') }}" required>
                                 @error('birthdate')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                         </div>
                                          </div>
                     
                     <div class="row mt-4">
                         <div class="col-12">
                             <h5 class="fw-bold mb-3">Informações do Sistema (Somente Leitura)</h5>
                             <div class="row">
                                 <div class="col-md-3">
                                     <p><strong>ID:</strong> {{ $estabelecimento['id'] ?? 'N/A' }}</p>
                                 </div>
                                 <div class="col-md-3">
                                     <p><strong>Documento:</strong> {{ $estabelecimento['document'] ?? 'N/A' }}</p>
                                 </div>
                                 <div class="col-md-3">
                                     <p><strong>Status:</strong> 
                                         @if(isset($estabelecimento['status']))
                                             @if($estabelecimento['status'] === 'APPROVED')
                                                 <span class="badge badge-success">Aprovado</span>
                                             @elseif($estabelecimento['status'] === 'PENDING')
                                                 <span class="badge badge-warning">Pendente</span>
                                             @elseif($estabelecimento['status'] === 'REJECTED')
                                                 <span class="badge badge-danger">Rejeitado</span>
                                             @else
                                                 <span class="badge badge-secondary">{{ $estabelecimento['status'] }}</span>
                                             @endif
                                         @else
                                             <span class="badge badge-secondary">N/A</span>
                                         @endif
                                     </p>
                                 </div>
                                 <div class="col-md-3">
                                     <p><strong>Risco:</strong> 
                                         @if(isset($estabelecimento['risk']))
                                             @if($estabelecimento['risk'] === 'LOW')
                                                 <span class="badge badge-success">Baixo</span>
                                             @elseif($estabelecimento['risk'] === 'MEDIUM')
                                                 <span class="badge badge-warning">Médio</span>
                                             @elseif($estabelecimento['risk'] === 'HIGH')
                                                 <span class="badge badge-danger">Alto</span>
                                             @else
                                                 <span class="badge badge-secondary">{{ $estabelecimento['risk'] }}</span>
                                             @endif
                                         @else
                                             <span class="badge badge-secondary">N/A</span>
                                         @endif
                                     </p>
                                 </div>
                             </div>
                         </div>
                     </div>
                     
                     <div class="row mt-4">
                         <div class="col-12">
                             <button type="submit" class="btn btn-primary">
                                 <i class="fas fa-save me-2"></i>Salvar Alterações
                             </button>
                             <a href="{{ route('estabelecimentos.show', $estabelecimento['id']) }}" class="btn btn-secondary ms-2">
                                 Cancelar
                             </a>
                         </div>
                     </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection 