document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.ship-select').forEach(sel=>{
    sel.addEventListener('change',()=>{
      const form=sel.closest('form');
      const btn=document.createElement('input');
      btn.type='hidden';
      btn.name='update';
      btn.value='1';
      form.appendChild(btn);
      form.submit();
    });
  });
});
