import{B as H,I as lt,D as st,J as ut,f as s,c as p,b as n,y as x,K as ct,L as W,A as Q,M as pt,h as X,j as y,F as _,d as k,t as c,a as V,N as bt,p as j,_ as vt,o as ft,O as K,i as h,m as b,r as F,n as ht,e as G,w as Z,P as mt,v as gt,g as yt}from"./index-6nPNuiHD.js";import{g as kt,a as wt,s as $t}from"./index-DPuWrvcQ.js";import{_ as xt}from"./SelectWeek-DKTiHvJ6.js";import{_ as q}from"./Card-D-5GEvo2.js";import{u as _t}from"./weeksStore-5s9CRThm.js";import{a as Bt}from"./date-5brAq0Ay.js";var Ct=`
    .p-radiobutton {
        position: relative;
        display: inline-flex;
        user-select: none;
        vertical-align: bottom;
        width: dt('radiobutton.width');
        height: dt('radiobutton.height');
    }

    .p-radiobutton-input {
        cursor: pointer;
        appearance: none;
        position: absolute;
        top: 0;
        inset-inline-start: 0;
        width: 100%;
        height: 100%;
        padding: 0;
        margin: 0;
        opacity: 0;
        z-index: 1;
        outline: 0 none;
        border: 1px solid transparent;
        border-radius: 50%;
    }

    .p-radiobutton-box {
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        border: 1px solid dt('radiobutton.border.color');
        background: dt('radiobutton.background');
        width: dt('radiobutton.width');
        height: dt('radiobutton.height');
        transition:
            background dt('radiobutton.transition.duration'),
            color dt('radiobutton.transition.duration'),
            border-color dt('radiobutton.transition.duration'),
            box-shadow dt('radiobutton.transition.duration'),
            outline-color dt('radiobutton.transition.duration');
        outline-color: transparent;
        box-shadow: dt('radiobutton.shadow');
    }

    .p-radiobutton-icon {
        transition-duration: dt('radiobutton.transition.duration');
        background: transparent;
        font-size: dt('radiobutton.icon.size');
        width: dt('radiobutton.icon.size');
        height: dt('radiobutton.icon.size');
        border-radius: 50%;
        backface-visibility: hidden;
        transform: translateZ(0) scale(0.1);
    }

    .p-radiobutton:not(.p-disabled):has(.p-radiobutton-input:hover) .p-radiobutton-box {
        border-color: dt('radiobutton.hover.border.color');
    }

    .p-radiobutton-checked .p-radiobutton-box {
        border-color: dt('radiobutton.checked.border.color');
        background: dt('radiobutton.checked.background');
    }

    .p-radiobutton-checked .p-radiobutton-box .p-radiobutton-icon {
        background: dt('radiobutton.icon.checked.color');
        transform: translateZ(0) scale(1, 1);
        visibility: visible;
    }

    .p-radiobutton-checked:not(.p-disabled):has(.p-radiobutton-input:hover) .p-radiobutton-box {
        border-color: dt('radiobutton.checked.hover.border.color');
        background: dt('radiobutton.checked.hover.background');
    }

    .p-radiobutton:not(.p-disabled):has(.p-radiobutton-input:hover).p-radiobutton-checked .p-radiobutton-box .p-radiobutton-icon {
        background: dt('radiobutton.icon.checked.hover.color');
    }

    .p-radiobutton:not(.p-disabled):has(.p-radiobutton-input:focus-visible) .p-radiobutton-box {
        border-color: dt('radiobutton.focus.border.color');
        box-shadow: dt('radiobutton.focus.ring.shadow');
        outline: dt('radiobutton.focus.ring.width') dt('radiobutton.focus.ring.style') dt('radiobutton.focus.ring.color');
        outline-offset: dt('radiobutton.focus.ring.offset');
    }

    .p-radiobutton-checked:not(.p-disabled):has(.p-radiobutton-input:focus-visible) .p-radiobutton-box {
        border-color: dt('radiobutton.checked.focus.border.color');
    }

    .p-radiobutton.p-invalid > .p-radiobutton-box {
        border-color: dt('radiobutton.invalid.border.color');
    }

    .p-radiobutton.p-variant-filled .p-radiobutton-box {
        background: dt('radiobutton.filled.background');
    }

    .p-radiobutton.p-variant-filled.p-radiobutton-checked .p-radiobutton-box {
        background: dt('radiobutton.checked.background');
    }

    .p-radiobutton.p-variant-filled:not(.p-disabled):has(.p-radiobutton-input:hover).p-radiobutton-checked .p-radiobutton-box {
        background: dt('radiobutton.checked.hover.background');
    }

    .p-radiobutton.p-disabled {
        opacity: 1;
    }

    .p-radiobutton.p-disabled .p-radiobutton-box {
        background: dt('radiobutton.disabled.background');
        border-color: dt('radiobutton.checked.disabled.border.color');
    }

    .p-radiobutton-checked.p-disabled .p-radiobutton-box .p-radiobutton-icon {
        background: dt('radiobutton.icon.disabled.color');
    }

    .p-radiobutton-sm,
    .p-radiobutton-sm .p-radiobutton-box {
        width: dt('radiobutton.sm.width');
        height: dt('radiobutton.sm.height');
    }

    .p-radiobutton-sm .p-radiobutton-icon {
        font-size: dt('radiobutton.icon.sm.size');
        width: dt('radiobutton.icon.sm.size');
        height: dt('radiobutton.icon.sm.size');
    }

    .p-radiobutton-lg,
    .p-radiobutton-lg .p-radiobutton-box {
        width: dt('radiobutton.lg.width');
        height: dt('radiobutton.lg.height');
    }

    .p-radiobutton-lg .p-radiobutton-icon {
        font-size: dt('radiobutton.icon.lg.size');
        width: dt('radiobutton.icon.lg.size');
        height: dt('radiobutton.icon.lg.size');
    }
`,St={root:function(e){var i=e.instance,u=e.props;return["p-radiobutton p-component",{"p-radiobutton-checked":i.checked,"p-disabled":u.disabled,"p-invalid":i.$pcRadioButtonGroup?i.$pcRadioButtonGroup.$invalid:i.$invalid,"p-variant-filled":i.$variant==="filled","p-radiobutton-sm p-inputfield-sm":u.size==="small","p-radiobutton-lg p-inputfield-lg":u.size==="large"}]},box:"p-radiobutton-box",input:"p-radiobutton-input",icon:"p-radiobutton-icon"},Pt=H.extend({name:"radiobutton",style:Ct,classes:St}),Rt={name:"BaseRadioButton",extends:lt,props:{value:null,binary:Boolean,readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:Pt,provide:function(){return{$pcRadioButton:this,$parentInstance:this}}};function B(t){"@babel/helpers - typeof";return B=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},B(t)}function zt(t,e,i){return(e=Ft(e))in t?Object.defineProperty(t,e,{value:i,enumerable:!0,configurable:!0,writable:!0}):t[e]=i,t}function Ft(t){var e=It(t,"string");return B(e)=="symbol"?e:e+""}function It(t,e){if(B(t)!="object"||!t)return t;var i=t[Symbol.toPrimitive];if(i!==void 0){var u=i.call(t,e);if(B(u)!="object")return u;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var Y={name:"RadioButton",extends:Rt,inheritAttrs:!1,emits:["change","focus","blur"],inject:{$pcRadioButtonGroup:{default:void 0}},methods:{getPTOptions:function(e){var i=e==="root"?this.ptmi:this.ptm;return i(e,{context:{checked:this.checked,disabled:this.disabled}})},onChange:function(e){if(!this.disabled&&!this.readonly){var i=this.binary?!this.checked:this.value;this.$pcRadioButtonGroup?this.$pcRadioButtonGroup.writeValue(i,e):this.writeValue(i,e),this.$emit("change",e)}},onFocus:function(e){this.$emit("focus",e)},onBlur:function(e){var i,u;this.$emit("blur",e),(i=(u=this.formField).onBlur)===null||i===void 0||i.call(u,e)}},computed:{groupName:function(){return this.$pcRadioButtonGroup?this.$pcRadioButtonGroup.groupName:this.$formName},checked:function(){var e=this.$pcRadioButtonGroup?this.$pcRadioButtonGroup.d_value:this.d_value;return e!=null&&(this.binary?!!e:ut(e,this.value))},dataP:function(){return st(zt({invalid:this.$invalid,checked:this.checked,disabled:this.disabled,filled:this.$variant==="filled"},this.size,this.size))}}},Nt=["data-p-checked","data-p-disabled","data-p"],Gt=["id","value","name","checked","tabindex","disabled","readonly","aria-labelledby","aria-label","aria-invalid"],Vt=["data-p"],jt=["data-p"];function Ot(t,e,i,u,v,a){return s(),p("div",x({class:t.cx("root")},a.getPTOptions("root"),{"data-p-checked":a.checked,"data-p-disabled":t.disabled,"data-p":a.dataP}),[n("input",x({id:t.inputId,type:"radio",class:[t.cx("input"),t.inputClass],style:t.inputStyle,value:t.value,name:a.groupName,checked:a.checked,tabindex:t.tabindex,disabled:t.disabled,readonly:t.readonly,"aria-labelledby":t.ariaLabelledby,"aria-label":t.ariaLabel,"aria-invalid":t.invalid||void 0,onFocus:e[0]||(e[0]=function(){return a.onFocus&&a.onFocus.apply(a,arguments)}),onBlur:e[1]||(e[1]=function(){return a.onBlur&&a.onBlur.apply(a,arguments)}),onChange:e[2]||(e[2]=function(){return a.onChange&&a.onChange.apply(a,arguments)})},a.getPTOptions("input")),null,16,Gt),n("div",x({class:t.cx("box")},a.getPTOptions("box"),{"data-p":a.dataP}),[n("div",x({class:t.cx("icon")},a.getPTOptions("icon"),{"data-p":a.dataP}),null,16,jt)],16,Vt)],16,Nt)}Y.render=Ot;var Dt=`
    .p-radiobutton-group {
        display: inline-flex;
    }
`,Lt={root:"p-radiobutton-group p-component"},Tt=H.extend({name:"radiobuttongroup",style:Dt,classes:Lt}),At={name:"BaseRadioButtonGroup",extends:ct,style:Tt,provide:function(){return{$pcRadioButtonGroup:this,$parentInstance:this}}},tt={name:"RadioButtonGroup",extends:At,inheritAttrs:!1,data:function(){return{groupName:this.name}},watch:{name:function(e){this.groupName=e||W("radiobutton-group-")}},mounted:function(){this.groupName=this.groupName||W("radiobutton-group-")}};function Et(t,e,i,u,v,a){return s(),p("div",x({class:t.cx("root")},t.ptmi("root")),[Q(t.$slots,"default")],16)}tt.render=Et;const Jt={__name:"Alert",props:{severity:{type:String,default:"info"},icon:{type:String,default:null},message:{type:String,default:null},closable:{type:Boolean,default:!0}},setup(t){var v,a;const e=t,i=j(()=>e.icon?e.icon:{info:"pi pi-info-circle",success:"pi pi-check-circle",warning:"pi pi-exclamation-triangle",error:"pi pi-times-circle"}[e.severity]||"pi pi-info-circle"),u=((a=(v=pt()).default)==null?void 0:a.call(v).length)>0;return(g,C)=>(s(),X(V(bt),{closable:t.closable,severity:t.severity,icon:i.value},{default:y(()=>[u?Q(g.$slots,"default",{key:0}):(s(),p(_,{key:1},[k(c(t.message),1)],64))]),_:3},8,["closable","severity","icon"]))}},Mt={class:"row"},Ut={class:"col-6 p-4"},Wt={class:"flex flex-col gap-2"},Kt={class:"col-6 p-4"},Zt={class:"flex flex-col gap-2"},qt={key:1},Ht={class:"row mt-2"},Qt={class:"col-4"},Xt={class:"col-4"},Yt={class:"col-4"},te={class:"grid-container"},ee={class:"grid-time"},oe=["onClick"],ne={key:0},ae={key:1},ie={key:0,class:"config-panel"},re={class:"row"},de=["id","value"],le=["for"],se={class:"flex flex-col gap-2"},ue={class:"flex items-center gap-2"},ce={class:"flex items-center gap-2"},pe={value:"slot"},be={__name:"ContraintesView",setup(t){const e=b(!1),i=j(()=>!e.value)??!0,u=b([]),v=b(null),a=b(null),g=["Lundi","Mardi","Mercredi","Jeudi","Vendredi"],C=b(["8h00","9h30","11h00","14h00","15h30","17h00"]),f=b(null),w=b([]),I=b(!1),O=b(""),d=b({day:"",time:"",week:"",weeks:[],type:"mandatory",duration:"slot"}),D=_t(),et=r=>{a.value=r},ot=j(()=>a.value),N=b(0),S=b(0),P=b(0);ft(async()=>{const o={departement:localStorage.getItem("departement")};u.value=await kt(o),await D.fetchWeeks(),w.value=D.weeks.member,a.value=w.value[0],$()});const $=()=>{if(S.value=0,P.value=0,console.log(f.value),!f.value){N.value=g.length*C.value.length;return}Object.values(f.value).forEach(r=>{r.type==="mandatory"?S.value++:r.type==="optional"&&P.value++}),N.value=g.length*C.value.length-S.value-P.value},nt=(r,o)=>{d.value.day=r,d.value.time=o,d.value.week=a.value,d.value.weeks=[],O.value=`${r}_${o}`,I.value=!0},at=()=>{const r=`${d.value.day}_${d.value.time}`;f.value[r]={type:d.value.type,duration:d.value.duration,week:d.value.week,weeks:d.value.weeks},console.log(f.value[r]),I.value=!1,$()},L=async(r,o)=>{try{f.value={};const z=(await yt.get(`/api/edt/personnels-contraintes/${a.value.semaineFormation}?personnel=${r}`)).data;f.value=z.contraintes,$()}catch(R){console.error("Error fetching constraints:",R)}},it=()=>{d.value.weeks.length===Object.values(w.value).length?d.value.weeks=[]:d.value.weeks=Object.values(w.value).map(r=>r.id)};K([v,a],([r,o])=>{r&&o?L(r.personnel.id,o.semaineFormation):(f.value={},$())}),K([a],([r])=>{r?L(v.value?v.value.personnel.id:null,r.semaineFormation):(f.value={},$())});const rt=r=>{const o=new Date(r);return g[o.getDay()-1]+" "+o.toLocaleDateString("fr-FR").slice(0,-5)};return(r,o)=>{var A,E;const R=wt,z=$t,T=Y,dt=tt;return s(),p("div",null,[h(q,{title:"Contraintes de disponibilité"},{default:y(()=>[n("div",Mt,[n("div",Ut,[n("div",Wt,[o[3]||(o[3]=n("label",{for:"professor-select"},"Choisir un professeur :",-1)),h(R,{class:"form-select d-block",modelValue:v.value,"onUpdate:modelValue":o[0]||(o[0]=l=>v.value=l),disabled:!V(i),options:u.value,optionLabel:"personnel.display"},null,8,["modelValue","disabled","options"])])]),n("div",Kt,[n("div",Zt,[h(xt,{"onUpdate:selectedWeek":et,"current-week":a.value},null,8,["current-week"])])])])]),_:1}),k(" "+c(v.value)+" - "+c(a.value)+" ",1),h(q,{title:`Saisir les contraintes de disponibilité pour ${(A=v.value)==null?void 0:A.personnel.display}, semaine ${(E=a.value)==null?void 0:E.semaineFormation}`},{default:y(()=>[ot.value?(s(),p("div",qt,[n("div",Ht,[n("div",Qt,[n("p",null,"Créneaux disponibles: "+c(N.value),1)]),n("div",Xt,[n("p",null,"Indisponible strict: "+c(S.value),1)]),n("div",Yt,[n("p",null,"Indisponible facultatif: "+c(P.value),1)])]),n("div",te,[o[5]||(o[5]=n("div",{class:"grid-header"},null,-1)),(s(!0),p(_,null,F(a.value.jours,l=>(s(),p("div",{key:l,class:"grid-header"},c(rt(l)),1))),128)),(s(!0),p(_,null,F(C.value,l=>(s(),p("div",{key:l,class:"grid-row"},[n("div",ee,c(l),1),(s(),p(_,null,F(g,m=>{var J,M,U;return n("div",{key:m,class:ht(["grid-cell",(J=f.value[`${m}_${l}`])==null?void 0:J.type,{selected:O.value===`${m}_${l}`}]),onClick:ve=>nt(m,l)},[((M=f.value[`${m}_${l}`])==null?void 0:M.type)==="mandatory"?(s(),p("span",ne,"Indispo. strict")):G("",!0),((U=f.value[`${m}_${l}`])==null?void 0:U.type)==="optional"?(s(),p("span",ae,"Indispo. facultatif")):G("",!0)],10,oe)}),64))]))),128))]),I.value?(s(),p("div",ie,[o[15]||(o[15]=n("h3",null,"Configurer la contrainte",-1)),n("p",null,"Semaine courante: "+c(d.value.week.semaineFormation)+" - "+c(d.value.week.semaineReelle),1),h(z,{onClick:it,severity:"info"},{default:y(()=>[...o[6]||(o[6]=[k("Cocher/Décocher toutes les semaines",-1)])]),_:1}),n("div",re,[o[7]||(o[7]=k(" Semaines : ",-1)),(s(!0),p(_,null,F(w.value,l=>(s(),p("div",{key:l.week,class:"col-3"},[Z(n("input",{type:"checkbox",id:`week-${l.id}`,value:l.semaineFormation,"onUpdate:modelValue":o[1]||(o[1]=m=>d.value.weeks=m)},null,8,de),[[mt,d.value.weeks]]),n("label",{for:`week-${l.id}`},"Semaine "+c(l.semaineFormation)+" ("+c(V(Bt)(l.dateLundi))+")",9,le)]))),128))]),n("p",null,"Jour: "+c(d.value.day),1),n("p",null,"Créneau: "+c(d.value.time),1),n("div",se,[o[10]||(o[10]=n("label",{for:"cheese"},"Indiquez le type de contrainte",-1)),h(dt,{name:"ingredient",class:"flex flex-wrap gap-4"},{default:y(()=>[n("div",ue,[h(T,{inputId:"mandatory",value:"mandatory"}),o[8]||(o[8]=n("label",{for:"mandatory"},"Obligatoire",-1))]),n("div",ce,[h(T,{inputId:"optional",value:"optional"}),o[9]||(o[9]=n("label",{for:"optional"},"Facultatif",-1))])]),_:1})]),o[16]||(o[16]=n("label",{for:"duration-select"},"Durée:",-1)),Z(n("select",{"onUpdate:modelValue":o[2]||(o[2]=l=>d.value.duration=l)},[n("option",pe,"Créneau ("+c(d.value.time)+")",1),o[11]||(o[11]=n("option",{value:"half-day"},"Demi-journée (matin)",-1)),o[12]||(o[12]=n("option",{value:"half-day"},"Demi-journée (après-midi)",-1)),o[13]||(o[13]=n("option",{value:"full-day"},"Journée entière",-1))],512),[[gt,d.value.duration]]),h(z,{class:"mt-2",severity:"primary",onClick:at},{default:y(()=>[...o[14]||(o[14]=[k("Enregistrer ",-1)])]),_:1})])):G("",!0)])):(s(),X(Jt,{key:0,severity:"warn"},{default:y(()=>[...o[4]||(o[4]=[k(" Veuillez sélectionner un professeur et une semaine pour afficher la grille. ",-1)])]),_:1}))]),_:1},8,["title"])])}}},we=vt(be,[["__scopeId","data-v-a9923d5d"]]);export{we as default};
