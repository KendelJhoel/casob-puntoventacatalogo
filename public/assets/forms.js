const tipo = document.querySelector('#tipo');
if (tipo) {
  const actualizar = () => {
    document.querySelectorAll('[data-campo-tipo]').forEach((grupo) => {
      const activo = grupo.dataset.campoTipo === tipo.value;
      grupo.hidden = !activo;
      const control = grupo.querySelector('input');
      control.disabled = !activo;
      control.required = activo;
    });
  };
  tipo.addEventListener('change', actualizar);
  actualizar();
}
