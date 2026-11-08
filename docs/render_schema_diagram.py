#!/usr/bin/env python3
"""Render this repository's review DBML as a self-contained offline HTML diagram.

Run: python3 docs/render_schema_diagram.py
This deliberately reads the generated DBML subset; it is not a general DBML parser.
"""
import hashlib
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
source = ROOT / 'database-schema-proposal.dbml'
text = source.read_text()
groups = {name: body.split() for name, body in re.findall(
    r'^TableGroup (\w+) \{\n(.*?)^\}', text, re.M | re.S)}
tables = []
for name, body in re.findall(r'^Table (\w+) [^\n]*\{\n(.*?)^\}', text, re.M | re.S):
    note = re.search(r"  Note: '((?:\\.|[^'])*)'", body).group(1).replace("\\'", "'")
    fields = []
    for line in body.splitlines():
        match = re.match(r'^  (\w+) ([\w(),]+)(?: \[(.*)\])?$', line)
        if match:
            field, kind, attributes = match.groups()
            attributes = attributes or ''
            fields.append({'name': field, 'type': kind, 'attributes': attributes,
                           'pk': bool(re.search(r'(?:^|, )pk(?:,|$)', attributes))})
    indexes = re.search(r'  indexes \{\n(.*?)  \}', body, re.S)
    tables.append({'name': name, 'fields': fields, 'note': note,
                   'status': re.match(r'\[(\w+)\]', note).group(1),
                   'group': next(k for k, v in groups.items() if name in v),
                   'indexes': [line.strip() for line in indexes[1].splitlines()] if indexes else []})
refs = [dict(zip(('from', 'field', 'to', 'target', 'delete'), values)) for values in re.findall(
    r'^Ref: (\w+)\.(\w+) > (\w+)\.(\w+) \[delete: ([a-z ]+)\]', text, re.M)]
lookup = {table['name']: table for table in tables}
for ref in refs:
    assert ref['field'] in {f['name'] for f in lookup[ref['from']]['fields']}, ref
    assert ref['target'] in {f['name'] for f in lookup[ref['to']]['fields']}, ref
assert len(lookup) == len(tables) == sum(len(names) for names in groups.values())
data = {'tables': tables, 'refs': refs, 'groups': groups,
        'source': source.name, 'hash': hashlib.sha256(source.read_bytes()).hexdigest()[:12]}
template = r'''<!doctype html>
<html lang="ar" dir="rtl">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Qader · Database diagram</title>
<style>
:root{font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:#192a3c;background:#f4f6f8;font-size:14px}
*{box-sizing:border-box}body{margin:0}button,input{font:inherit}button,a{touch-action:manipulation}button{cursor:pointer}
header{height:82px;background:white;border-bottom:1px solid #dce2e8;display:flex;align-items:center;justify-content:space-between;padding:0 26px;gap:20px}
h1{font-size:21px;margin:0 0 5px}header p{margin:0;color:#637386;font-size:12px}.brand{color:#254cf0;font-weight:800;letter-spacing:2px;font-size:16px;margin-left:12px}
.badge{font-size:12px;background:#fff4d6;padding:7px 11px;border-radius:5px;color:#73510d;white-space:nowrap}a{color:#274dd8}
.app{display:flex;height:calc(100dvh - 82px);min-height:450px}.sidebar{width:235px;flex-shrink:0;background:white;border-left:1px solid #dce2e8;padding:20px 14px;overflow:auto}
.sidebar h2{font-size:12px;color:#788391;margin:23px 9px 9px;font-weight:600}.search{width:100%;padding:10px;border:1px solid #ccd5df;border-radius:6px;text-align:left;direction:ltr;outline:none}.search:focus{border-color:#254cf0}
.navbtn{width:100%;border:0;background:transparent;display:flex;align-items:center;justify-content:space-between;padding:10px;border-radius:5px;text-align:right;color:#34465a;margin:2px 0;gap:6px}.navbtn:hover{background:#f1f4f8}.navbtn.active{background:#eaf0ff;color:#2545c9;font-weight:600}.count{font-size:11px;opacity:.65;direction:ltr}
.result{direction:ltr;text-align:left;font-size:12px;overflow-wrap:anywhere}.main{flex:1;min-width:0;display:flex;flex-direction:column;position:relative}
.toolbar{background:#fff;padding:13px 18px;border-bottom:1px solid #dce2e8;display:flex;align-items:center;gap:9px;flex-wrap:wrap}.heading{margin-left:auto}.heading strong{display:block;font-size:15px}.heading small{display:block;color:#788391;font-size:11px;margin-top:4px}
.btn{border:1px solid #d7dfe8;background:white;color:#273b52;border-radius:5px;padding:7px 10px}.btn:hover{background:#eff3fa}.btn:focus-visible,.navbtn:focus-visible{outline:2px solid #254cf0;outline-offset:2px}.control{display:flex;align-items:center;gap:5px;font-size:12px;white-space:nowrap}.zoom{min-width:46px;font-size:12px;text-align:center;direction:ltr}
.stage{position:relative;flex:1;overflow:hidden;direction:ltr;background-image:radial-gradient(#dce2e9 .8px,transparent .8px);background-size:20px 20px;touch-action:none;cursor:grab}.stage:active{cursor:grabbing}svg{width:100%;height:100%;display:block;user-select:none}.node{cursor:pointer;outline:none}.node:focus-visible .outline{stroke:#254cf0;stroke-width:3}.edge{fill:none;stroke:#bcc7d3;stroke-width:1.4}.edge.active{stroke:#254cf0;stroke-width:2.3}.node.dim{opacity:.28}
.footer{padding:10px 18px;display:flex;gap:14px;flex-wrap:wrap;font-size:11px;color:#67778a;background:white;border-top:1px solid #dce2e8}.legend{display:flex;align-items:center;gap:5px}.dot{width:8px;height:8px;border-radius:2px;display:inline-block}.hint{margin-right:auto}
.details{width:350px;background:white;border-right:1px solid #dce2e8;overflow:auto;flex-shrink:0;padding:20px;direction:rtl}.details[hidden]{display:none}.details h2{font:600 16px ui-monospace,monospace;direction:ltr;overflow-wrap:anywhere;margin:20px 0 8px}.details h3{font-size:12px;color:#718092;margin:25px 0 10px}.details p{font-size:12px;line-height:1.7;direction:ltr;text-align:left;overflow-wrap:anywhere}.fields{width:100%;border-collapse:collapse;direction:ltr;font-size:11px}.fields td{padding:8px 3px;border-bottom:1px solid #e9edf2;vertical-align:top;overflow-wrap:anywhere}.fields td:first-child{font-family:ui-monospace,monospace;max-width:160px}.fields td:last-child{color:#718092}.pk{color:#2554c0;font-weight:700}.relation{display:block;text-align:left;direction:ltr;overflow-wrap:anywhere;width:100%;margin:5px 0;padding:9px;background:#f5f7fa;border:0;border-radius:4px;font-size:11px;color:#304b6c}.index{font:11px ui-monospace,monospace;direction:ltr;text-align:left;padding:6px 0;overflow-wrap:anywhere}.close{float:left}.noresults{font-size:12px;padding:10px;color:#788391}
@media(max-width:900px){.sidebar{width:185px}.details{position:absolute;left:0;top:82px;bottom:0;z-index:5;width:min(350px,90vw)}header{padding:0 15px}.hint{display:none}.control{font-size:11px}}
@media(max-width:580px){.sidebar{width:135px;padding:12px 7px}.navbtn{font-size:11px;padding:9px 5px}.brand{display:none}.badge{display:none}h1{font-size:17px}.toolbar{padding:9px;gap:6px}.heading{width:100%}.footer{gap:7px;padding:8px}.control{font-size:10px}}
</style>
<header><div><h1><span class="brand" dir="ltr">QADER</span> خريطة قاعدة البيانات</h1><p id="source"></p></div><div class="badge">مقترح للمراجعة · لم يُنفّذ</div></header>
<div class="app">
 <aside class="sidebar"><input class="search" id="search" placeholder="Search tables…" aria-label="البحث عن جدول"><div id="results" hidden></div><nav id="nav" aria-label="مجموعات الجداول"></nav><h2>ملف المصدر</h2><a href="database-schema-proposal.dbml" style="font-size:12px;padding:10px;display:block">فتح ملف DBML</a><p style="font-size:11px;color:#788391;padding:0 9px;line-height:1.7">نسخة محلية مستقلة. تعمل دون إنترنت.<br>بعد تعديل السكيما أعد توليد العرض بالأمر الموجود في نهاية الملف.</p></aside>
 <main class="main"><div class="toolbar"><div class="heading"><strong id="viewTitle"></strong><small id="viewMeta"></small></div>
 <label class="control"><input type="checkbox" id="neighbors">العلاقات الخارجية</label><label class="control"><input type="checkbox" id="expanded">كل الأعمدة</label>
 <button class="btn" id="minus" aria-label="تصغير">−</button><span class="zoom" id="zoom">100%</span><button class="btn" id="plus" aria-label="تكبير">+</button><button class="btn" id="fit">احتواء</button><button class="btn" id="download">حفظ SVG</button></div>
 <div class="stage" id="stage"><svg id="svg" aria-label="ديجرام الجداول والعلاقات" role="img"><defs><marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#8799ac"/></marker></defs><g id="world"></g></svg></div>
 <div class="footer"><span class="legend"><i class="dot" style="background:#557fa8"></i>موجود</span><span class="legend"><i class="dot" style="background:#be913a"></i>تعديل</span><span class="legend"><i class="dot" style="background:#43967d"></i>جديد</span><span class="legend"><i class="dot" style="background:#9a85af"></i>مقترح / مؤجل</span><span class="hint">اسحب للتحريك · عجلة الماوس للتكبير · اضغط جدولًا للتفاصيل · السهم من FK إلى الجدول المرجعي</span></div></main>
 <aside class="details" id="details" hidden aria-label="تفاصيل الجدول"></aside>
</div>
<script id="schema" type="application/json">__DATA__</script>
<script>
'use strict';
const DATA=JSON.parse(document.getElementById('schema').textContent);
const $=id=>document.getElementById(id), NS='http://www.w3.org/2000/svg';
const TABLES=Object.fromEntries(DATA.tables.map(t=>[t.name,t]));
const LABELS={Identity:'الحسابات',Access:'الصلاحيات',Reference:'البيانات المرجعية',Talent:'ملف المرشح',Infrastructure:'البنية الأساسية',Applications:'التقديم والاعتماد',Workspace:'مساحات العمل',Commercial:'أنواع العقود والرسوم',Hiring:'طلبات التوظيف والعروض',Interviews:'المقابلات',Evaluation:'التقييمات',Support:'طلبات المساعدة',JobsForms:'الوظائف ونماذج التقديم',Finance:'المدفوعات والعملات',Contracts:'العقود والتوظيف',PostContract:'ما بعد التعاقد',Operations:'السجلات والإشعارات'};
const STATUS={KEEP:['موجود','#557fa8','#edf4fb'],CHANGE:['تعديل مقترح','#be913a','#fff8e9'],NEW:['جديد','#43967d','#edf7f3'],PROPOSED:['اقتراح','#9a85af','#f5f1f8'],DOC:['من الوثائق','#9a85af','#f5f1f8'],DEFERRED:['مؤجل','#9a85af','#f5f1f8']};
const CORE=['users','clients','talents','workspaces','talent_applications','hiring_requests','hiring_request_items','hiring_offers','interviews','talent_evaluations','contracts','invoices','payments'];
let view='overview',selected=null,positions={},renderedRefs=[],scale=1,tx=30,ty=30,bounds={w:1000,h:700},drag=null;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function svgEl(type,attrs,parent,text){const e=document.createElementNS(NS,type);for(const [k,v] of Object.entries(attrs||{}))e.setAttribute(k,String(v));if(text!==undefined)e.textContent=text;if(parent)parent.append(e);return e;}
function button(label,key,count){const b=document.createElement('button');b.className='navbtn';b.dataset.view=key;b.innerHTML='<span>'+esc(label)+'</span><span class="count">'+count+'</span>';b.onclick=()=>switchView(key);$('nav').append(b);}
button('العلاقات الأساسية','overview',CORE.length);button('كل الجداول','all',DATA.tables.length);
const nh=document.createElement('h2');nh.textContent='الموديولات';$('nav').append(nh);
Object.keys(DATA.groups).forEach(g=>button(LABELS[g]||g,g,DATA.groups[g].length));
$('source').textContent=DATA.tables.length+' جدول · '+DATA.refs.length+' علاقة · المصدر: '+DATA.source+' · '+DATA.hash;
function namesInView(){let names=view==='overview'?[...CORE]:view==='all'?DATA.tables.map(t=>t.name):view.startsWith('table:')?[view.slice(6)]:[...DATA.groups[view]];const base=new Set(names);if($('neighbors').checked||view.startsWith('table:')){for(const r of DATA.refs)if(base.has(r.from)||base.has(r.to)){names.push(r.from,r.to);}}return {names:[...new Set(names)],base};}
function visibleFields(t){if($('expanded').checked)return t.fields;const refs=new Set(DATA.refs.filter(r=>r.from===t.name).map(r=>r.field));const important=t.fields.filter(f=>f.pk||refs.has(f.name)||f.name==='status');return important.slice(0,view==='overview'?3:7);}
// Condense graph cycles before laying out parent-to-child ranks.
function layout(names){const set=new Set(names),adj=Object.fromEntries(names.map(n=>[n,[]]));for(const r of DATA.refs)if(set.has(r.from)&&set.has(r.to)&&r.from!==r.to)adj[r.to].push(r.from);
 let index=0,stack=[],on=new Set(),idx={},low={},components=[];
 function visit(v){idx[v]=low[v]=index++;stack.push(v);on.add(v);for(const w of adj[v]){if(idx[w]===undefined){visit(w);low[v]=Math.min(low[v],low[w]);}else if(on.has(w))low[v]=Math.min(low[v],idx[w]);}if(low[v]===idx[v]){const c=[];let w;do{w=stack.pop();on.delete(w);c.push(w);}while(w!==v);components.push(c);}}
 names.forEach(n=>{if(idx[n]===undefined)visit(n);});const ci={};components.forEach((c,i)=>c.forEach(n=>ci[n]=i));const parents=components.map(()=>new Set());for(const a of names)for(const b of adj[a])if(ci[a]!==ci[b])parents[ci[b]].add(ci[a]);const ranks={};function rank(i){if(ranks[i]!==undefined)return ranks[i];return ranks[i]=parents[i].size?1+Math.max(...[...parents[i]].map(rank)):0;}
 const cols={};for(const n of names){const r=rank(ci[n]);(cols[r]??=[]).push(n);}const p={};let maxY=0;
 for(const [r,items] of Object.entries(cols)){let y=35;items.sort((a,b)=>names.indexOf(a)-names.indexOf(b));for(const n of items){const fields=visibleFields(TABLES[n]),more=TABLES[n].fields.length-fields.length;const h=68+fields.length*23+(more?23:0);p[n]={x:35+Number(r)*370,y,w:290,h,fields,more};y+=h+34;}maxY=Math.max(maxY,y);}
 bounds={w:Math.max(...Object.values(p).map(p=>p.x+p.w))+35,h:maxY};return p;}
function draw(){const {names,base}=namesInView();positions=layout(names);const world=$('world');world.replaceChildren();renderedRefs=DATA.refs.filter(r=>positions[r.from]&&positions[r.to]);
 const edges=svgEl('g',{},world);for(const r of renderedRefs){const a=positions[r.from],b=positions[r.to];const row=Math.max(0,a.fields.findIndex(f=>f.name===r.field));let start={x:a.x,y:a.y+67+row*23},end={x:b.x+b.w,y:b.y+67};if(a.x===b.x){start.x=a.x+a.w;end.x=b.x+b.w;}const dx=a.x===b.x?65:Math.max(45,Math.abs(start.x-end.x)*.48);const d=a.x===b.x?`M${start.x},${start.y} C${start.x+dx},${start.y} ${end.x+dx},${end.y} ${end.x},${end.y}`:`M${start.x},${start.y} C${start.x-dx},${start.y} ${end.x+dx},${end.y} ${end.x},${end.y}`;
 const path=svgEl('path',{d,class:'edge','marker-end':'url(#arrow)','data-from':r.from,'data-to':r.to},edges);svgEl('title',{},path,`${r.from}.${r.field} → ${r.to}.${r.target} · delete: ${r.delete}`);}
 for(const n of names){const p=positions[n],t=TABLES[n],st=STATUS[t.status],ext=!base.has(n);const g=svgEl('g',{class:'node',transform:`translate(${p.x},${p.y})`,tabindex:0,role:'button','aria-label':n,'data-name':n},world);svgEl('rect',{class:'outline',width:p.w,height:p.h,rx:7,fill:'#fff',stroke:ext?'#bcc6d1':'#d4dde6','stroke-dasharray':ext?'5 3':''},g);svgEl('path',{d:`M7,0 H283 Q290,0 290,7 V46 H0 V7 Q0,0 7,0`,fill:st[2]},g);svgEl('rect',{x:12,y:16,width:5,height:15,rx:2,fill:st[1]},g);svgEl('text',{x:25,y:27,fill:'#1d344e','font-family':'ui-monospace,monospace','font-size':n.length>30?11.5:13,'font-weight':600},g,n);svgEl('text',{x:13,y:58,fill:'#8b98a8','font-size':9},g,`${t.group} · ${t.status}${ext?' · related':''}`);
 p.fields.forEach((f,i)=>{const fk=DATA.refs.some(r=>r.from===n&&r.field===f.name);svgEl('text',{x:13,y:81+i*23,fill:f.pk||fk?'#3156a3':'#34495e','font-family':'ui-monospace,monospace','font-size':11},g,(f.pk?'◆ ':fk?'↗ ':'  ')+f.name);svgEl('text',{x:277,y:81+i*23,'text-anchor':'end',fill:'#92a0af','font-size':10},g,f.type);});if(p.more)svgEl('text',{x:14,y:p.h-13,fill:'#7c8d9f','font-size':10},g,`+ ${p.more} columns · click for details`);
 g.addEventListener('click',()=>{if(!drag?.moved)showDetails(n);});g.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();showDetails(n);}});}
 $('viewTitle').textContent=view==='overview'?'العلاقات الأساسية بين الموديولات':view==='all'?'كل الجداول':view.startsWith('table:')?view.slice(6):(LABELS[view]||view);
 $('viewMeta').textContent=`${names.length} جدول ظاهر · ${renderedRefs.length} علاقة${view==='overview'?' · عرض مختصر؛ اختر موديولًا للتفاصيل':''}`;
 document.querySelectorAll('.navbtn').forEach(b=>b.classList.toggle('active',b.dataset.view===view));highlight();fit();}
function transform(){scale=Math.max(.08,Math.min(2.5,scale));$('world').setAttribute('transform',`translate(${tx},${ty}) scale(${scale})`);$('zoom').textContent=Math.round(scale*100)+'%';}
function fit(){const rect=$('stage').getBoundingClientRect();scale=Math.min(1,Math.max(.08,Math.min((rect.width-40)/bounds.w,(rect.height-40)/bounds.h)));tx=Math.max(20,(rect.width-bounds.w*scale)/2);ty=Math.max(20,(rect.height-bounds.h*scale)/2);transform();}
function zoomBy(f,x,y){const rect=$('stage').getBoundingClientRect();x??=rect.width/2;y??=rect.height/2;const next=Math.max(.08,Math.min(2.5,scale*f));tx=x-(x-tx)*next/scale;ty=y-(y-ty)*next/scale;scale=next;transform();}
function switchView(v){view=v;selected=null;$('details').hidden=true;draw();}
function highlight(){const related=new Set([selected]);for(const r of renderedRefs)if(r.from===selected||r.to===selected){related.add(r.from);related.add(r.to);}document.querySelectorAll('.edge').forEach(e=>{const active=e.dataset.from===selected||e.dataset.to===selected;e.classList.toggle('active',active);e.style.opacity=selected && !active ? .14 : 1;});document.querySelectorAll('.node').forEach(e=>{e.classList.toggle('dim',Boolean(selected&&!related.has(e.dataset.name)));e.querySelector('.outline').setAttribute('stroke',e.dataset.name===selected?'#254cf0':'#d4dde6');});}
function showDetails(n){selected=n;const t=TABLES[n],links=DATA.refs.filter(r=>r.from===n||r.to===n);$('details').hidden=false;$('details').innerHTML=`<button class="btn close" id="closeDetails" aria-label="إغلاق التفاصيل">×</button><h2>${esc(n)}</h2><small>${esc(STATUS[t.status][0])} · ${esc(LABELS[t.group])}</small><p>${esc(t.note)}</p><button class="btn" id="focusTable">اعرض علاقات هذا الجدول فقط</button><h3>الأعمدة · ${t.fields.length}</h3><table class="fields"><tbody>${t.fields.map(f=>`<tr><td class="${f.pk?'pk':''}" title="${esc(f.attributes)}">${esc(f.name)}${f.pk?' · PK':''}</td><td>${esc(f.type)}<br>${f.attributes.includes('not null')||f.pk?'required':'nullable'}${f.attributes.includes('unique')?' · unique':''}</td></tr>`).join('')}</tbody></table><h3>العلاقات · ${links.length}</h3>${links.map((r,i)=>`<button class="relation" data-rel="${i}">${esc(r.from+'.'+r.field)} → ${esc(r.to+'.'+r.target)}<br><span style="color:#8090a1">ON DELETE ${esc(r.delete.toUpperCase())}</span></button>`).join('')}${t.indexes.length?'<h3>Indexes</h3>'+t.indexes.map(i=>'<div class="index">'+esc(i)+'</div>').join(''):''}`;
 $('closeDetails').onclick=()=>{selected=null;$('details').hidden=true;highlight();fit();};$('focusTable').onclick=()=>{view='table:'+n;draw();};$('details').querySelectorAll('[data-rel]').forEach(b=>b.onclick=()=>{const r=links[Number(b.dataset.rel)],target=r.from===n?r.to:r.from;if(!positions[target]){view='table:'+target;draw();}showDetails(target);});highlight();}
$('search').addEventListener('input',()=>{const q=$('search').value.trim().toLowerCase();$('results').hidden=!q;$('nav').hidden=Boolean(q);$('results').replaceChildren();if(!q)return;const matches=DATA.tables.filter(t=>t.name.toLowerCase().includes(q));for(const t of matches){const b=document.createElement('button');b.className='navbtn result';b.textContent=t.name;b.onclick=()=>{view='table:'+t.name;draw();showDetails(t.name);};$('results').append(b);}if(!matches.length)$('results').innerHTML='<p class="noresults">لا توجد جداول بهذا الاسم.</p>';});
$('neighbors').onchange=draw;$('expanded').onchange=draw;$('fit').onclick=fit;$('plus').onclick=()=>zoomBy(1.25);$('minus').onclick=()=>zoomBy(.8);
$('stage').addEventListener('wheel',e=>{e.preventDefault();const r=$('stage').getBoundingClientRect();zoomBy(e.deltaY<0?1.1:1/1.1,e.clientX-r.left,e.clientY-r.top);},{passive:false});
$('stage').addEventListener('pointerdown',e=>{if(e.button!==0)return;drag={id:e.pointerId,x:e.clientX,y:e.clientY,tx,ty,moved:false};});
window.addEventListener('pointermove',e=>{if(!drag||drag.id!==e.pointerId)return;if(Math.hypot(e.clientX-drag.x,e.clientY-drag.y)>4)drag.moved=true;if(drag.moved){tx=drag.tx+e.clientX-drag.x;ty=drag.ty+e.clientY-drag.y;transform();}});
window.addEventListener('pointerup',()=>setTimeout(()=>drag=null,0));window.addEventListener('pointercancel',()=>drag=null);window.addEventListener('resize',fit);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){selected=null;$('details').hidden=true;highlight();fit();}});
$('download').onclick=()=>{const copy=$('svg').cloneNode(true);copy.setAttribute('xmlns',NS);copy.setAttribute('width',bounds.w);copy.setAttribute('height',bounds.h);copy.setAttribute('viewBox',`0 0 ${bounds.w} ${bounds.h}`);copy.querySelector('#world').removeAttribute('transform');copy.querySelectorAll('.edge').forEach(p=>{p.setAttribute('fill','none');p.setAttribute('stroke','#9aabba');p.setAttribute('stroke-width','1.4');p.style.opacity=1;});copy.querySelectorAll('.node').forEach(n=>n.style.opacity=1);const blob=new Blob([new XMLSerializer().serializeToString(copy)],{type:'image/svg+xml'});const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='qader-'+view.replace(/[^a-z0-9_-]/gi,'-')+'.svg';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);};
draw();
window.qaderDiagram={data:DATA,get view(){return view;},get visibleTables(){return Object.keys(positions);}};
</script>
<!-- Regenerate after DBML edits: python3 docs/render_schema_diagram.py -->
</html>'''
# JSON is data, never executable HTML; prevent closing the embedding script element.
output = template.replace('__DATA__', json.dumps(data, ensure_ascii=False).replace('<', '\\u003c'))
destination = ROOT / 'database-schema-diagram.html'
destination.write_text(output)
print(f'Created {destination.name}: {len(tables)} tables, {len(refs)} foreign keys, {len(groups)} modules.')
