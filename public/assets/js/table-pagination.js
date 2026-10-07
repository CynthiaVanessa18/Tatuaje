(() => {
  let tableSequence = 0;
  const initialized = new WeakSet();
  function initializeTables(root = document) {
    root.querySelectorAll('table').forEach(table => {
      if (initialized.has(table)) return;
      initialized.add(table);
      let wrapper = table.parentElement;
      if (!wrapper.matches('.table-scroll, .table-responsive, .responsive-table-scroll')) {
        const scroll = document.createElement('div');
        scroll.className = 'responsive-table-scroll';
        table.before(scroll);
        scroll.append(table);
        wrapper = scroll;
      }
      wrapper.tabIndex = 0;
      wrapper.setAttribute('role', 'region');
      const name = table.caption?.textContent.trim() || table.closest('section')?.querySelector('h2,h3')?.textContent.trim() || 'Tabla de registros';
      wrapper.setAttribute('aria-label', name);
      if (table.hasAttribute('data-server-paginated')) return;
      const rows = Array.from(table.tBodies).flatMap(body => Array.from(body.rows));
      if (!rows.length || (rows.length === 1 && rows[0].cells.length === 1 && rows[0].cells[0].colSpan > 1)) return;
      const nav = document.createElement('nav');
      nav.className = 'table-pagination';
      nav.setAttribute('aria-label', `Páginas de ${name}`);
      const status = document.createElement('span');
      status.className = 'table-pagination-status';
      status.setAttribute('role', 'status');
      const prev = document.createElement('button');
      prev.type = 'button'; prev.textContent = '← Anterior';
      const next = document.createElement('button');
      next.type = 'button'; next.textContent = 'Siguiente →';
      const numbers = document.createElement('div');
      numbers.className = 'actions';
      const sizeLabel = document.createElement('label');
      sizeLabel.className = 'table-pagination-size';
      sizeLabel.textContent = 'Filas por página';
      const select = document.createElement('select');
      select.id = `table-page-size-${++tableSequence}`;
      select.setAttribute('aria-label', 'Filas por página');
      [5, 10, 20, 50].forEach(value => select.add(new Option(String(value), String(value))));
      select.value = table.matches('.membership-edit-table,.membership-table') ? '5' : '10';
      sizeLabel.append(select);
      let page = 1;
      const showPage = value => {
        const size = Number(select.value);
        const totalPages = Math.max(1, Math.ceil(rows.length / size));
        page = Math.max(1, Math.min(value, totalPages));
        rows.forEach((row, index) => { row.hidden = index < (page - 1) * size || index >= page * size; });
        prev.disabled = page === 1; next.disabled = page === totalPages;
        status.textContent = `${rows.length} filas · Página ${page} de ${totalPages} · Mostrando ${(page - 1) * size + 1}–${Math.min(page * size, rows.length)}`;
        numbers.replaceChildren();
        const visiblePages = new Set([1, totalPages, page - 1, page, page + 1].filter(n => n >= 1 && n <= totalPages));
        let last = 0;
        [...visiblePages].sort((a, b) => a - b).forEach(n => {
          if (last && n - last > 1) { const gap = document.createElement('span'); gap.textContent = '…'; numbers.append(gap); }
          const button = document.createElement('button');
          button.type = 'button'; button.textContent = String(n); button.setAttribute('aria-label', `Página ${n}`);
          if (n === page) button.setAttribute('aria-current', 'page');
          button.addEventListener('click', () => showPage(n)); numbers.append(button); last = n;
        });
      };
      prev.addEventListener('click', () => showPage(page - 1));
      next.addEventListener('click', () => showPage(page + 1));
      select.addEventListener('change', () => showPage(1));
      // Los campos de todas las páginas siguen en el formulario y se envían juntos.
      table.addEventListener('invalid', event => {
        const index = rows.indexOf(event.target.closest('tr'));
        if (index >= 0) showPage(Math.floor(index / Number(select.value)) + 1);
        const editor = event.target.closest('[data-rule-text-editor]');
        if (editor?.hidden) {
          editor.hidden = false;
          const caption = editor.closest('tr').querySelector('[data-rule-caption]');
          if (caption) caption.hidden = true;
          editor.closest('tr').querySelector('[data-rule-edit]')?.setAttribute('aria-expanded', 'true');
        }
      }, true);
      nav.append(status, prev, numbers, next, sizeLabel);
      wrapper.after(nav);
      showPage(1);
    });
  }
  initializeTables();
  // También prepara las tablas que se cargan al navegar por la tienda.
  new MutationObserver(records => {
    if (records.some(record => Array.from(record.addedNodes).some(node => node.nodeType === 1 && (node.matches('main,section,table') || node.querySelector('table'))))) initializeTables();
  }).observe(document.body, {childList: true, subtree: true});
})();
