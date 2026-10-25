document.querySelectorAll('[data-balance-search]').forEach(form => {
    const input=form.querySelector('[data-balance-name]');
    const selection=form.querySelector('[data-balance-selection]');
    const matches=form.querySelector('[role=listbox]');
    const status=form.querySelector('[data-balance-status]');
    let timer, request, active=-1, results=[];
    const close=() => { matches.hidden=true;input.setAttribute('aria-expanded','false');input.removeAttribute('aria-activedescendant');active=-1; };
    const choose=card => {
        clearTimeout(timer);request?.abort();
        selection.value=card.destinatario;input.value=card.nombre_destinatario;
        const currency=form.querySelector('[data-balance-currency]');if(currency)currency.value=card.moneda;
        close();form.requestSubmit();
    };
    const load=async () => {
        request?.abort();
        const term=input.value.trim();
        if (!term) { close();status.textContent='';return; }
        const currentRequest=new AbortController();request=currentRequest;
        status.textContent='Buscando coincidencias…';
        const url=new URL(form.action,location.href);
        url.search=new URLSearchParams({module:'saldos_tarjetas',suggest:'1',q:term,estado:form.querySelector('[name=estado]:checked')?.value||''});
        try {
            const response=await fetch(url,{signal:currentRequest.signal,headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error('Search unavailable');
            const cards=await response.json();
            if (input.value.trim()!==term || request!==currentRequest) return;
            results=cards;active=-1;matches.replaceChildren();
            cards.forEach((card,index) => {
                const button=document.createElement('button');button.type='button';button.id=`balance-match-${index}`;button.setAttribute('role','option');button.setAttribute('aria-selected','false');
                const name=document.createElement('strong');name.textContent=card.nombre_destinatario;
                const detail=document.createElement('small');detail.textContent=`${card.tarjetas} tarjetas · Saldo disponible: ${card.moneda} ${Number(card.saldo_actual).toLocaleString('es-CR',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
                button.append(name,detail);button.addEventListener('click',()=>choose(card));matches.append(button);
            });
            matches.hidden=!cards.length;input.setAttribute('aria-expanded',String(!!cards.length));
            status.textContent=cards.length?`${cards.length} coincidencias. Selecciona una o pulsa Buscar para verlas todas.`:'No hay coincidencias con ese nombre y estado.';
        } catch(error) {
            if (error.name==='AbortError') return;
            close();status.textContent='No se pudieron cargar las sugerencias. Puedes pulsar Buscar para consultar.';
        }
    };
    input.addEventListener('input',()=>{selection.value='';form.querySelector('[data-balance-currency]')?.remove();request?.abort();close();clearTimeout(timer);timer=setTimeout(load,250);});
    input.addEventListener('focus',load);
    input.addEventListener('keydown',event=>{
        if (event.key==='Escape') {close();return;}
        if (matches.hidden) return;
        if (event.key==='ArrowDown'||event.key==='ArrowUp') {
            event.preventDefault();active=(active+(event.key==='ArrowDown'?1:-1)+results.length)%results.length;
            Array.from(matches.children).forEach((option,index)=>option.setAttribute('aria-selected',String(index===active)));
            input.setAttribute('aria-activedescendant',matches.children[active].id);matches.children[active].scrollIntoView({block:'nearest'});
        } else if (event.key==='Enter' && active>=0) {event.preventDefault();choose(results[active]);}
    });
    form.querySelectorAll('[name=estado]').forEach(radio=>radio.addEventListener('change',()=>form.requestSubmit()));
    document.addEventListener('click',event=>{if (!event.target.closest('.balance-autocomplete')) close();});
});
