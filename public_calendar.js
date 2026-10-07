document.addEventListener('DOMContentLoaded',()=>{
const root=document.querySelector('.lc-public-calendar');if(!root)return;
const grid=root.querySelector('[data-grid]'),title=root.querySelector('[data-month]'),detail=root.querySelector('[data-detail]'),sync=root.querySelector('[data-sync]');
let month=new Date();month.setDate(1);let selected=null,dates={},blocked=new Set();
const pad=n=>String(n).padStart(2,'0');
const iso=(y,m,d)=>y+'-'+pad(m+1)+'-'+pad(d);
function renderDetail(day){
 selected=day;detail.replaceChildren();
 const heading=document.createElement('strong');heading.textContent=new Date(day+'T12:00:00').toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});detail.append(heading);
 const info=dates[day]||{available:[],reserved:[]};
 if(blocked.has(day)||info.reserved.length){const el=document.createElement('p');el.textContent='This date is reserved or blocked. Please select a green date.';detail.append(el);return;}if(!info.available.length){const el=document.createElement('p');el.textContent='Available by default, 09:00–18:00. Select a package to book.';detail.append(el);return;}
 for(const [key,label] of [['available','Available times'],['reserved','Reserved times']]){
  if(!info[key].length)continue;
  const h=document.createElement('div');h.className='lc-public-time-title';h.textContent=label;detail.append(h);
  const wrap=document.createElement('div');wrap.className='lc-public-times';
  for(const time of info[key]){const tag=document.createElement('span');tag.className='lc-public-time '+(key==='available'?'free':'taken');tag.textContent=time;wrap.append(tag);}
  detail.append(wrap);
 }
 if(info.available.length){const p=document.createElement('p');p.className='lc-public-hint';p.textContent='Choose a package to continue with booking.';detail.append(p);}
}
function draw(){
 title.textContent=month.toLocaleDateString('en-US',{month:'long',year:'numeric'});grid.replaceChildren();
 for(const d of ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']){const e=document.createElement('span');e.className='lc-public-weekday';e.textContent=d;grid.append(e);}
 for(let i=0;i<month.getDay();i++){const e=document.createElement('span');grid.append(e);}
 const last=new Date(month.getFullYear(),month.getMonth()+1,0).getDate();
 for(let d=1;d<=last;d++){
  const day=iso(month.getFullYear(),month.getMonth(),d),info=dates[day]||{available:[],reserved:[]};
  const e=document.createElement('button');e.type='button';e.textContent=d;
  e.className='lc-public-day '+(blocked.has(day)||info.reserved.length?'taken':day>=iso(new Date().getFullYear(),new Date().getMonth(),new Date().getDate())?'free':'none')+(selected===day?' selected':'');
  e.setAttribute('aria-label',day+(info.available.length?' available slots':info.reserved.length?' reserved':' no published availability'));
  e.addEventListener('click',()=>{renderDetail(day);draw();});grid.append(e);
 }
}
root.querySelector('[data-prev]').addEventListener('click',()=>{month.setMonth(month.getMonth()-1);draw();});
root.querySelector('[data-next]').addEventListener('click',()=>{month.setMonth(month.getMonth()+1);draw();});
async function refresh(){
 try{const res=await fetch(root.dataset.feed,{cache:'no-store',credentials:'same-origin'});if(!res.ok)throw Error('Fetch failed');
 const json=await res.json();dates=json.dates||{};blocked=new Set(json.blocked||[]);draw();if(selected)renderDetail(selected);sync.textContent='Updated '+new Date().toLocaleTimeString();
 }catch(e){sync.textContent='Calendar unavailable. Please refresh to retry.';}
}
draw();refresh();setInterval(()=>{if(!document.hidden)refresh();},30000);
});
