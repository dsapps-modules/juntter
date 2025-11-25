@props(['label' => 'Enviar', 'name' => 'submit'])

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
    <button type="submit" name="submit" value="{{ $name }}" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>
        {{ $label }}
    </button>
</div>
