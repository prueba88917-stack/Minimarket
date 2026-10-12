// Scripts generales para Mini Abarrotes Don Luis
// Incluye efectos interactivos, cálculos dinámicos de vuelto y reproducción de audio de feedback

document.addEventListener('DOMContentLoaded', () => {
  // Manejo de sonido con Web Audio API sintetizado para no requerir archivos externos pesados
  window.reproducirSonido = function(tipo) {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      if (tipo === 'beep' || tipo === 'venta') {
        // Tono agradable de caja registradora / venta
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08); // A5
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.35);
      } else if (tipo === 'alerta') {
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(300, ctx.currentTime);
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.25);
      }
    } catch (e) {
      console.log('Audio no soportado o bloqueado por navegador', e);
    }
  };

  // Cálculo de vuelto interactivo en ventas
  const montoRecibidoInput = document.getElementById('monto_recibido');
  const totalVentaInput = document.getElementById('total_venta_hidden');
  const vueltoDisplay = document.getElementById('vuelto_display');
  const vueltoInput = document.getElementById('vuelto_hidden');

  if (montoRecibidoInput && totalVentaInput && vueltoDisplay) {
    montoRecibidoInput.addEventListener('input', () => {
      const total = parseFloat(totalVentaInput.value) || 0;
      const recibido = parseFloat(montoRecibidoInput.value) || 0;
      const vuelto = Math.max(0, recibido - total);
      
      vueltoDisplay.textContent = '$' + vuelto.toLocaleString('es-CL');
      if (vueltoInput) {
        vueltoInput.value = vuelto;
      }
      
      if (recibido >= total && total > 0) {
        vueltoDisplay.style.color = '#059669';
      } else {
        vueltoDisplay.style.color = '#ef4444';
      }
    });
  }

  // Confirmación al eliminar o realizar acciones delicadas
  const dangerButtons = document.querySelectorAll('.confirm-action');
  dangerButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      const msg = btn.getAttribute('data-confirm') || '¿Está seguro de realizar esta acción?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
