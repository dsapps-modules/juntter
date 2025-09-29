@extends('templates.dashboard-template')

@section('title', 'Dashboard')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Admin'"
    :saldos="$saldos"
    :metricas="$metricas"
    :metricasGeral="$metricasGeral"
    :metricasCartao="$metricasCartao"
    :metricasBoleto="$metricasBoleto"
    :mesAtual="$mesAtual"
    :anoAtual="$anoAtual"
    :breadcrumbItems="[
        ['label' => 'Administração', 'icon' => 'fas fa-cogs', 'url' => '#']
    ]"
/>

       <!-- Seção de Estabelecimentos -->
       <div class="row mt-4">
           <div class="col-12">
               <div class="card border-0 shadow-lg rounded-4">
                   <div class="card-body p-3">
                       <!-- Título da seção -->
                       <div class="d-flex justify-content-between align-items-center mb-3">
                           <div>
                               <h3 class="h4 mb-1 fw-bold">Estabelecimentos</h3>
                               <p class="text-muted mb-0">Gerencie os estabelecimentos cadastrados</p>
                           </div>
                           <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-outline-primary btn-sm" onclick="atualizarDados(event)" title="Atualizar Dados">
                                   <i class="fas fa-sync-alt me-1"></i>
                                   Atualizar
                               </button>
                               @if(isset($ultima_atualizacao))
                               <small class="text-muted">
                                   <i class="fas fa-clock me-1"></i>
                                   Atualizado há <span id="tempo-atualizacao">{{ $ultima_atualizacao }}</span>
                               </small>
                               @endif
                               {{--  <button class="btn btn-novo-pagamento shadow-sm">
                                   <i class="fas fa-plus me-2"></i>
                                   Novo Estabelecimento
                               </button> --}}
                           </div>
                       </div>
                       
                       <!-- Tabela Juntter Style -->
                       <div class="table-responsive">
                           <table id="estabelecimentos-table" class="table table-hover table-striped">
                               <thead>
                                   <tr class="table-header-juntter">
                                       <th></th>
                                       <th>ID</th>
                                       <th>Nome</th>
                                       <th>Documento</th>
                                       <th>Email</th>
                                       <th>Telefone</th>
                                       <th>Cidade/UF</th>
                                       <th>Status</th>
                                       <th>Risco</th>
                                       <th>Ações</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   @foreach($estabelecimentos['data'] ?? [] as $estabelecimento)
                                       <tr>
                                           <td></td>
                                           <td><strong>{{ $estabelecimento['id'] ?? 'N/A' }}</strong></td>
                                           <td>
                                               <strong>{{ $estabelecimento['first_name'] ?? $estabelecimento['name1'] ?? 'N/A' }}</strong>
                                               @if(isset($estabelecimento['last_name']))
                                                   <br><small class="text-muted">{{ $estabelecimento['last_name'] }}</small>
                                               @endif
                                           </td>
                                           <td><span class="text-muted">{{ $estabelecimento['document'] ?? 'N/A' }}</span></td>
                                           <td><span class="text-muted">{{ $estabelecimento['email'] ?? 'N/A' }}</span></td>
                                           <td><span class="text-muted">{{ $estabelecimento['phone_number'] ?? 'N/A' }}</span></td>
                                           <td><span class="text-muted">{{ $estabelecimento['address']['city'] ?? 'N/A' }}/{{ $estabelecimento['address']['state'] ?? 'N/A' }}</span></td>
                                           <td>
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
                                           </td>
                                           <td>
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
                                           </td>
                                           <td>
                                               <div class="btn-group" role="group">
                                                   <a href="{{ route('estabelecimentos.show', $estabelecimento['id']) }}" 
                                                      class="btn btn-sm btn-outline-info mr-1" title="Visualizar">
                                                       <i class="fas fa-eye"></i>
                                                   </a>
                                                   <a href="{{ route('estabelecimentos.edit', $estabelecimento['id']) }}" 
                                                      class="btn btn-sm btn-outline-warning" title="Editar">
                                                       <i class="fas fa-edit"></i>
                                                   </a>
                                               </div>
                                           </td>
                                       </tr>
                                   @endforeach
                               </tbody>
                    </table>
                                       </div>
                   </div>
               </div>
           </div>
       </div>


@endsection

@push('scripts')
<script>
function atualizarDados(event) {
    const $btn = $(event.target);
    const originalText = $btn.html();
    
    // Mostrar loading
    $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Atualizando...').prop('disabled', true);
    
    // Fazer requisição para limpar cache e recarregar
    $.ajax({
        url: '{{ route("admin.limpar-cache") }}',
        method: 'POST',
        data: {
            mes: '{{ request("mes") }}',
            ano: '{{ request("ano") }}'
        },
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(data) {
            // Mostrar "agora" imediatamente
            $('#tempo-atualizacao').text('agora');
            
            // Mostrar sucesso
            $btn.html('<i class="fas fa-check me-1"></i>Atualizado!')
                .removeClass('btn-outline-primary')
                .addClass('btn-success');
            
            // Recarregar página após 1 segundo
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            $btn.html('<i class="fas fa-exclamation-triangle me-1"></i>Erro!')
                .removeClass('btn-outline-primary')
                .addClass('btn-danger');
            
            // Restaurar botão após 3 segundos
            setTimeout(() => {
                $btn.html(originalText)
                    .removeClass('btn-danger')
                    .addClass('btn-outline-primary')
                    .prop('disabled', false);
            }, 3000);
        }
    });
}

// Atualizar tempo em tempo real usando jQuery
function atualizarTempoAtualizacao() {
    const $elemento = $('#tempo-atualizacao');
    
    if ($elemento.length && $elemento.text().trim() !== '') {
        try {
            const agora = new Date();
            let tempoAtualizacao;
            
            const dataTexto = $elemento.text().trim();
            if (dataTexto.includes('-') && dataTexto.includes(' ')) {
                // Formato: 2025-09-16 14:31:20
                tempoAtualizacao = new Date(dataTexto.replace(' ', 'T') + 'Z');
            } else {
                tempoAtualizacao = new Date(dataTexto);
            }
            
            // Verificar se a data é válida
            if (isNaN(tempoAtualizacao.getTime())) {
                $elemento.text('agora');
                return;
            }
            
            const diffMs = agora - tempoAtualizacao;
            const diffMinutos = Math.floor(diffMs / 60000);
            
            if (diffMinutos < 1) {
                $elemento.text('agora');
            } else if (diffMinutos < 60) {
                $elemento.text(`${diffMinutos} min`);
            } else {
                const diffHoras = Math.floor(diffMinutos / 60);
                $elemento.text(`${diffHoras}h ${diffMinutos % 60}min`);
            }
        } catch (e) {
            $elemento.text('agora');
        }
    }
}

// Executar quando o documento estiver pronto
$(document).ready(function() {
    // Executar imediatamente
    atualizarTempoAtualizacao();
    
    // Executar a cada minuto
    setInterval(atualizarTempoAtualizacao, 60000);
});
</script>
@endpush










