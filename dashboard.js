document.addEventListener('DOMContentLoaded',()=>{
 const el=document.querySelector('.lc-calendar');
 if(!el)return;
 let month=new Date();month.setDate(1);
 let available=new Set(JSON.parse(el.dataset.available||'[]'));
 let booked=new Set(JSON.parse(el.dataset.booked||'[]'));
 let bookings=JSON.parse(el.dataset.bookings||'[]');
 const days=el.querySelector('[data-cal-days]');
 const title=el.querySelector('[data-cal-title]');
 const detail=el.querySelector('[data-cal-detail]');
 const sync=el.querySelector('[data-cal-sync]');
 const pad=n=>String(n).padStart(2,'0');
 const isoDate=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
 let selected=null;
 function showDate(iso){
  selected=iso;
  detail.replaceChildren();
  const heading=document.createElement('strong');heading.textContent='Bookings · '+iso;detail.append(heading);
  const matches=bookings.filter(b=>b.booking_date===iso);
  if(!matches.length){const p=document.createElement('p');p.textContent=available.has(iso)?'No bookings yet. Available slots may remain.':'No active bookings on this date.';detail.append(p);return;}
  for(const b of matches){const row=document.createElement('div');row.className='lc-cal-detail-row';
   const who=document.createElement('strong');who.textContent=b.fullname+' · '+b.package_name;
   const time=document.createElement('small');time.textContent=b.start_time.slice(0,5)+'–'+b.end_time.slice(0,5)+' · '+b.status+' · '+b.booking_code;
   row.append(who,time);detail.append(row);
  }
 }
 function draw(){
  title.textContent=month.toLocaleDateString('en-US',{month:'long',year:'numeric'});days.replaceChildren();
  ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(t=>{const x=document.createElement('span');x.className='lc-weekday';x.textContent=t;days.append(x)});
  for(let i=0;i<month.getDay();i++){const x=document.createElement('span');x.className='lc-cal-blank';days.append(x)}
  const last=new Date(month.getFullYear(),month.getMonth()+1,0).getDate();
  for(let d=1;d<=last;d++){
   const iso=month.getFullYear()+'-'+pad(month.getMonth()+1)+'-'+pad(d);
   const x=document.createElement('button');x.type='button';x.textContent=d;
   x.className='lc-cal-day'+(booked.has(iso)?' is-booked':iso>=isoDate(new Date())?' is-available':'')+(selected===iso?' is-selected':'');
   x.title=iso+(booked.has(iso)?' · Reserved or blocked':' · Available by default');
   x.setAttribute('aria-label',x.title);x.addEventListener('click',()=>{showDate(iso);draw()});days.append(x);
  }
 }
 el.querySelector('[data-cal-prev]').addEventListener('click',()=>{month.setMonth(month.getMonth()-1);draw()});
 el.querySelector('[data-cal-next]').addEventListener('click',()=>{month.setMonth(month.getMonth()+1);draw()});
 draw();
 async function refresh(){
  try{const res=await fetch(el.dataset.sync,{credentials:'same-origin',cache:'no-store'});if(!res.ok)throw new Error('Could not refresh');
   const data=await res.json();bookings=data.bookings;booked=new Set(data.booked);available=new Set(data.available);
   draw();if(selected)showDate(selected);
   sync.textContent='Calendar updated · '+new Date().toLocaleTimeString();
  }catch(e){sync.textContent='Could not sync calendar. Refresh this page to retry.'}
 }
 const timer=setInterval(()=>{if(!document.hidden)refresh()},20000);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh()});
 document.querySelectorAll('.lc-side-link[href^="#"]').forEach(link=>link.addEventListener('click',()=>{document.querySelectorAll('.lc-side-link').forEach(x=>x.classList.remove('active'));link.classList.add('active')}));
});
