<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_login();
if(!cart())redirect('cart.php');
$ids=array_values(array_unique(array_filter(array_map('intval',array_column(cart(),'package_id')))));
if(!$ids)redirect('cart.php');
$marks=implode(',',array_fill(0,count($ids),'?'));
$q=db()->prepare("SELECT pa.id,pa.name,pa.price,pa.duration_hours,pa.photographer_id,p.studio_name,p.location,p.avatar_url FROM packages pa JOIN photographers p ON p.id=pa.photographer_id WHERE pa.id IN ($marks) AND p.is_active=1 AND pa.is_active=1 AND pa.stock_qty>0");
$q->bind_param(str_repeat('i',count($ids)),...$ids);$q->execute();$packs=$q->get_result()->fetch_all(MYSQLI_ASSOC);
// Publish all slots AND active reservations. The browser subtracts occupied intervals
// from published intervals; booked dates remain visible in red.
$slots=[];$reservations=[];
foreach($packs as $pack){$pid=(int)$pack['photographer_id'];if(isset($slots[$pid]))continue;
 $q=db()->prepare("SELECT available_date,start_time,end_time,is_booked FROM availability WHERE photographer_id=? AND available_date>=CURDATE() ORDER BY available_date,start_time LIMIT 1000");
 $q->bind_param('i',$pid);$q->execute();$slots[$pid]=$q->get_result()->fetch_all(MYSQLI_ASSOC);
 $q=db()->prepare("SELECT booking_date,start_time,end_time FROM bookings WHERE photographer_id=? AND booking_date>=CURDATE() AND status IN ('pending','confirmed') ORDER BY booking_date,start_time LIMIT 1000");
 $q->bind_param('i',$pid);$q->execute();$reservations[$pid]=$q->get_result()->fetch_all(MYSQLI_ASSOC);
}
$page_title='Book Your Photography Session | LensCraft';require __DIR__.'/partials_header.php';
?>
<section class="lc-booking-page container">
 <div class="lc-booking-top"><div><span class="eyebrow">YOUR MOMENT, PERFECTLY PLANNED</span><h1>Book your <span>perfect session.</span></h1><p>Choose your photographer, select a date, and reserve your moment.</p></div><a class="btn btn-ghost" href="cart.php"><i class="fa-solid fa-arrow-left"></i> Back to cart</a></div>
 <div class="lc-booking-steps"><div class="active"><span>1</span> Select package</div><i class="fa-solid fa-chevron-right"></i><div class="active"><span>2</span> Date &amp; time</div><i class="fa-solid fa-chevron-right"></i><div><span>3</span> Secure payment</div></div>
 <form method="post" action="create_booking.php" id="sessionForm" class="lc-booking-layout"><?= csrf_field() ?>
 <div class="lc-booking-main">
  <div class="lc-book-panel"><div class="lc-book-panel-title"><div class="lc-book-step-icon"><i class="fa-solid fa-camera"></i></div><div><span class="eyebrow">STEP 01</span><h2>Choose your package</h2><p>Select the photography experience you love.</p></div></div>
   <label for="packageChoice" class="lc-book-label">Photography package</label><select id="packageChoice" name="package_id" required><?php foreach($packs as $pack): ?><option value="<?= (int)$pack['id'] ?>" data-photographer="<?= (int)$pack['photographer_id'] ?>" data-studio="<?= e($pack['studio_name']) ?>" data-name="<?= e($pack['name']) ?>" data-price="<?= e((string)$pack['price']) ?>" data-location="<?= e($pack['location']) ?>"><?= e($pack['studio_name'].' — '.$pack['name'].' · '.money((float)$pack['price'])) ?></option><?php endforeach; ?></select>
  </div>
  <div class="lc-book-panel"><div class="lc-book-panel-title"><div class="lc-book-step-icon"><i class="fa-regular fa-calendar-check"></i></div><div><span class="eyebrow">STEP 02</span><h2>Pick your date</h2><p>All future dates are available unless booked or blocked by the photographer.</p></div></div>
   <div class="lc-book-calendar"><div class="lc-book-calendar-head"><button type="button" id="calPrev" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong id="calMonth"></strong><button type="button" id="calNext" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div><div id="calGrid" class="lc-book-calendar-grid"></div><div class="lc-book-legend"><span><i class="dot free"></i> Available</span><span><i class="dot booked"></i> Booked / blocked</span><span><i class="dot unavailable"></i> Past date</span><span><i class="dot selected"></i> Selected</span></div></div>
   <div class="lc-book-times"><h3><i class="fa-regular fa-clock"></i> Select your booking time</h3><p id="slotHint">Select a highlighted date to view its available sessions.</p><div id="timeSlots" class="lc-book-time-grid" role="group" aria-label="Available time slots"></div>
<div id="customTimePicker" class="lc-book-custom-times" hidden><label for="customerStart" class="lc-book-label">Start time</label><select id="customerStart"><option value="">Select start time</option></select><label for="customerEnd" class="lc-book-label">End time</label><select id="customerEnd"><option value="">Select end time</option></select><small>Set your booking start and end time below (30-minute steps). Only times within the available session can be selected.</small></div></div>
   <input type="hidden" name="booking_date" id="bookingDate"><input type="hidden" name="start_time" id="startTime"><input type="hidden" name="end_time" id="endTime">
  </div>
  <div class="lc-book-panel"><div class="lc-book-panel-title"><div class="lc-book-step-icon"><i class="fa-solid fa-pen-to-square"></i></div><div><span class="eyebrow">STEP 03</span><h2>Tell us about your event</h2><p>Share anything your photographer should know.</p></div></div><label for="sessionNotes" class="lc-book-label">Event details &amp; special requests <span>(optional)</span></label><textarea id="sessionNotes" name="notes" maxlength="2000" rows="5" placeholder="Example: Wedding ceremony at Kandy, outdoor portraits, preferred photo style, venue details..."></textarea><small class="lc-book-note"><i class="fa-solid fa-shield-halved"></i> Your notes will be shared with your chosen photographer.</small></div>
 </div>
 <aside class="lc-book-sidebar"><div class="lc-book-summary"><span class="eyebrow">YOUR RESERVATION</span><h2>Booking summary</h2><div class="lc-book-summary-photographer"><div class="lc-book-summary-avatar"><i class="fa-solid fa-camera-retro"></i></div><div><strong id="summaryStudio">—</strong><small id="summaryLocation">—</small></div></div><div class="lc-book-summary-row"><span><i class="fa-solid fa-box-open"></i> Package</span><strong id="summaryPackage">—</strong></div><div class="lc-book-summary-row"><span><i class="fa-regular fa-calendar"></i> Date</span><strong id="summaryDate">Not selected</strong></div><div class="lc-book-summary-row"><span><i class="fa-regular fa-clock"></i> Time</span><strong id="summaryTime">Not selected</strong></div><div class="lc-book-summary-total"><span>Total amount</span><strong id="summaryPrice">—</strong></div><button type="submit" id="reviewBtn" class="btn btn-primary btn-full" disabled>Continue to secure checkout <i class="fa-solid fa-arrow-right"></i></button><div class="lc-book-security"><i class="fa-solid fa-lock"></i> No payment is collected on this page.<br>Availability is checked again before booking.</div></div><div class="lc-book-help"><i class="fa-solid fa-circle-info"></i><p>Future dates are open by default (09:00–18:00). A photographer may publish different hours or block a date. Red dates are not bookable.</p></div></aside>
 </form>
</section>
<script>
(()=>{'use strict';
const slots=<?= json_encode($slots,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const reservations=<?= json_encode($reservations,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const pkg=document.getElementById('packageChoice'),grid=document.getElementById('calGrid'),title=document.getElementById('calMonth'),times=document.getElementById('timeSlots'),hint=document.getElementById('slotHint');
const date=document.getElementById('bookingDate'),start=document.getElementById('startTime'),end=document.getElementById('endTime'),btn=document.getElementById('reviewBtn'),picker=document.getElementById('customTimePicker'),customerStart=document.getElementById('customerStart'),customerEnd=document.getElementById('customerEnd');
let month=new Date();month.setDate(1);let chosenDay='';
const pad=n=>String(n).padStart(2,'0'),iso=(y,m,d)=>y+'-'+pad(m+1)+'-'+pad(d);
const photographerId=()=>pkg.selectedOptions[0]?.dataset.photographer;
const currentSlots=()=>slots[photographerId()]||[];
const currentReservations=()=>reservations[photographerId()]||[];
const toMinutes=t=>{const [h,m]=t.slice(0,5).split(':').map(Number);return h*60+m;};
const toClock=n=>pad(Math.floor(n/60))+':'+pad(n%60);
function isReserved(day){return currentReservations().some(x=>x.booking_date===day);}
function isBlocked(day){return currentSlots().some(x=>x.available_date===day&&Number(x.is_booked)===1);}
function freeWindows(day){
 if(day<iso(new Date().getFullYear(),new Date().getMonth(),new Date().getDate())||isReserved(day)||isBlocked(day))return [];
 // Default bookable hours. A photographer may publish a narrower open interval.
 const published=currentSlots().filter(x=>x.available_date===day&&Number(x.is_booked)===0);
 return (published.length?published:[{available_date:day,start_time:'09:00',end_time:'18:00'}]).map(x=>({available_date:day,start_time:x.start_time,end_time:x.end_time}));
}
function reservedWindows(day){return currentReservations().filter(x=>x.booking_date===day).map(x=>({start_time:x.start_time,end_time:x.end_time}));}
const formatDate=d=>new Date(d+'T12:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
const money=n=>'Rs. '+Number(n).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
function clearSelection(){date.value=start.value=end.value='';picker.hidden=true;customerStart.replaceChildren();customerEnd.replaceChildren();btn.disabled=true;document.getElementById('summaryTime').textContent='Not selected';}
function draw(){grid.replaceChildren();title.textContent=month.toLocaleDateString('en-US',{month:'long',year:'numeric'});
for(const label of ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']){const e=document.createElement('span');e.className='lc-book-weekday';e.textContent=label;grid.append(e);}
for(let i=0;i<month.getDay();i++){const blank=document.createElement('span');grid.append(blank);}
const last=new Date(month.getFullYear(),month.getMonth()+1,0).getDate();
for(let d=1;d<=last;d++){const day=iso(month.getFullYear(),month.getMonth(),d);const available=freeWindows(day).length>0;const reserved=isReserved(day)||isBlocked(day);const el=document.createElement('button');el.type='button';el.textContent=d;el.className='lc-book-day '+(available?'available':reserved?'booked':'unavailable')+(day===chosenDay?' picked':'');el.disabled=!available;el.setAttribute('aria-label',day+(available?' available':reserved?' booked':' unavailable'));if(available)el.addEventListener('click',()=>chooseDate(day));grid.append(el);}}
function chooseDate(day){chosenDay=day;clearSelection();date.value=day;document.getElementById('summaryDate').textContent=formatDate(day);times.replaceChildren();const list=freeWindows(day);const reserved=reservedWindows(day);hint.textContent=list.length+' available session'+(list.length===1?'':'s')+' for '+formatDate(day);
list.forEach((slot,i)=>{const el=document.createElement('button');el.type='button';el.className='lc-book-time';el.innerHTML='<i class="fa-regular fa-clock"></i> ';el.append(document.createTextNode(slot.start_time.slice(0,5)+' – '+slot.end_time.slice(0,5)));el.addEventListener('click',()=>{times.querySelectorAll('button').forEach(b=>b.classList.remove('picked'));el.classList.add('picked');configureTime(slot);});times.append(el);});reserved.forEach(slot=>{const el=document.createElement('button');el.type='button';el.disabled=true;el.className='lc-book-time is-reserved';el.textContent=slot.start_time.slice(0,5)+' – '+slot.end_time.slice(0,5)+' · Booked';times.append(el);});if(list.length){const first=times.querySelector('button:not(:disabled)');if(first){first.classList.add('picked');configureTime(list[0]);hint.textContent='Choose your start and end time below. You can also select a different available session.';}}draw();}
function minutes(t){const p=t.slice(0,5).split(':').map(Number);return p[0]*60+p[1];}
function clock(n){return pad(Math.floor(n/60))+':'+pad(n%60);}
function addOption(select,n){const o=document.createElement('option');o.value=clock(n);o.textContent=clock(n);select.append(o);}
function updateTime(){start.value=customerStart.value;end.value=customerEnd.value;const valid=!!start.value&&!!end.value&&minutes(end.value)>minutes(start.value);btn.disabled=!valid;document.getElementById('summaryTime').textContent=valid?start.value+' – '+end.value:'Not selected';}
function configureTime(slot){
 picker.hidden=false;start.value=end.value='';btn.disabled=true;
 const a=minutes(slot.start_time),b=minutes(slot.end_time);
 customerStart.replaceChildren();customerEnd.replaceChildren();
 const startPlaceholder=new Option('Select start time','');customerStart.add(startPlaceholder);
 const endPlaceholder=new Option('Select end time','');customerEnd.add(endPlaceholder);
 // Include the published endpoints, plus half-hour clock boundaries.
 const points=[a];for(let n=Math.ceil(a/30)*30;n<b;n+=30)if(n>a)points.push(n);points.push(b);
 for(const n of points.slice(0,-1))addOption(customerStart,n);
 for(const n of points.slice(1))addOption(customerEnd,n);
 customerStart.value=clock(a);customerEnd.value=clock(b);
 const adjust=()=>{for(const opt of customerEnd.options){if(opt.value)opt.disabled=minutes(opt.value)<=minutes(customerStart.value||'00:00');}if(customerEnd.value&&minutes(customerEnd.value)<=minutes(customerStart.value||'00:00'))customerEnd.value='';updateTime();};
 customerStart.onchange=adjust;customerEnd.onchange=updateTime;adjust();
}
function reset(){const option=pkg.selectedOptions[0];chosenDay='';clearSelection();document.getElementById('summaryDate').textContent='Not selected';document.getElementById('summaryStudio').textContent=option?.dataset.studio||'—';document.getElementById('summaryLocation').textContent=option?.dataset.location||'—';document.getElementById('summaryPackage').textContent=option?.dataset.name||'—';document.getElementById('summaryPrice').textContent=money(option?.dataset.price||0);times.replaceChildren();hint.textContent='Select any green date to choose your start and end time.';month=new Date();month.setDate(1);draw();}
pkg.addEventListener('change',reset);document.getElementById('calPrev').addEventListener('click',()=>{month.setMonth(month.getMonth()-1);draw();});document.getElementById('calNext').addEventListener('click',()=>{month.setMonth(month.getMonth()+1);draw();});document.getElementById('sessionForm').addEventListener('submit',e=>{if(!date.value||!start.value||!end.value){e.preventDefault();btn.disabled=true;}});reset();
})();
</script>
<?php require __DIR__.'/partials_footer.php'; ?>
