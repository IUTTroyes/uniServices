import{aR as g,o as i,e as s,w as u,p as C,U as p,V as c,g as $,K as h,c7 as D,j as v,Q as B,O as f,aS as b,ar as F,aX as N,a_ as E,bp as I,b3 as _,b6 as U,bw as T,b5 as j,aj as P,c as y,F as O,m as L,d as k,t as z,br as V}from"./index-DdW8-gy6.js";var R={root:"p-accordioncontent",contentWrapper:"p-accordioncontent-wrapper",content:"p-accordioncontent-content"},W=g.extend({name:"accordioncontent",classes:R}),Q={name:"BaseAccordionContent",extends:b,props:{as:{type:[String,Object],default:"DIV"},asChild:{type:Boolean,default:!1}},style:W,provide:function(){return{$pcAccordionContent:this,$parentInstance:this}}},w={name:"AccordionContent",extends:Q,inheritAttrs:!1,inject:["$pcAccordion","$pcAccordionPanel"],computed:{id:function(){return"".concat(this.$pcAccordion.$id,"_accordioncontent_").concat(this.$pcAccordionPanel.value)},ariaLabelledby:function(){return"".concat(this.$pcAccordion.$id,"_accordionheader_").concat(this.$pcAccordionPanel.value)},attrs:function(){return c(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){return{id:this.id,role:"region","aria-labelledby":this.ariaLabelledby,"data-pc-name":"accordioncontent","data-p-active":this.$pcAccordionPanel.active}},ptParams:function(){return{context:{active:this.$pcAccordionPanel.active}}}}};function M(e,n,t,o,d,a){return e.asChild?h(e.$slots,"default",{key:1,class:f(e.cx("root")),active:a.$pcAccordionPanel.active,a11yAttrs:a.a11yAttrs}):(i(),s(B,c({key:0,name:"p-collapsible"},e.ptm("transition",a.ptParams)),{default:u(function(){return[!a.$pcAccordion.lazy||a.$pcAccordionPanel.active?C((i(),s(p(e.as),c({key:0,class:e.cx("root")},a.attrs),{default:u(function(){return[$("div",c({class:e.cx("contentWrapper")},e.ptm("contentWrapper",a.ptParams)),[$("div",c({class:e.cx("content")},e.ptm("content",a.ptParams)),[h(e.$slots,"default")],16)],16)]}),_:3},16,["class"])),[[D,a.$pcAccordion.lazy?!0:a.$pcAccordionPanel.active]]):v("",!0)]}),_:3},16))}w.render=M;var X={root:"p-accordionheader",toggleicon:"p-accordionheader-toggle-icon"},q=g.extend({name:"accordionheader",classes:X}),G={name:"BaseAccordionHeader",extends:b,props:{as:{type:[String,Object],default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:q,provide:function(){return{$pcAccordionHeader:this,$parentInstance:this}}},K={name:"AccordionHeader",extends:G,inheritAttrs:!1,inject:["$pcAccordion","$pcAccordionPanel"],methods:{onFocus:function(){this.$pcAccordion.selectOnFocus&&this.changeActiveValue()},onClick:function(){!this.$pcAccordion.selectOnFocus&&this.changeActiveValue()},onKeydown:function(n){switch(n.code){case"ArrowDown":this.onArrowDownKey(n);break;case"ArrowUp":this.onArrowUpKey(n);break;case"Home":this.onHomeKey(n);break;case"End":this.onEndKey(n);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(n);break}},onArrowDownKey:function(n){var t=this.findNextPanel(this.findPanel(n.currentTarget));t?this.changeFocusedPanel(n,t):this.onHomeKey(n),n.preventDefault()},onArrowUpKey:function(n){var t=this.findPrevPanel(this.findPanel(n.currentTarget));t?this.changeFocusedPanel(n,t):this.onEndKey(n),n.preventDefault()},onHomeKey:function(n){var t=this.findFirstPanel();this.changeFocusedPanel(n,t),n.preventDefault()},onEndKey:function(n){var t=this.findLastPanel();this.changeFocusedPanel(n,t),n.preventDefault()},onEnterKey:function(n){this.changeActiveValue(),n.preventDefault()},findPanel:function(n){return n==null?void 0:n.closest('[data-pc-name="accordionpanel"]')},findHeader:function(n){return j(n,'[data-pc-name="accordionheader"]')},findNextPanel:function(n){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,o=t?n:n.nextElementSibling;return o?T(o,"data-p-disabled")?this.findNextPanel(o):this.findHeader(o):null},findPrevPanel:function(n){var t=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,o=t?n:n.previousElementSibling;return o?T(o,"data-p-disabled")?this.findPrevPanel(o):this.findHeader(o):null},findFirstPanel:function(){return this.findNextPanel(this.$pcAccordion.$el.firstElementChild,!0)},findLastPanel:function(){return this.findPrevPanel(this.$pcAccordion.$el.lastElementChild,!0)},changeActiveValue:function(){this.$pcAccordion.updateValue(this.$pcAccordionPanel.value)},changeFocusedPanel:function(n,t){U(this.findHeader(t))}},computed:{id:function(){return"".concat(this.$pcAccordion.$id,"_accordionheader_").concat(this.$pcAccordionPanel.value)},ariaControls:function(){return"".concat(this.$pcAccordion.$id,"_accordioncontent_").concat(this.$pcAccordionPanel.value)},attrs:function(){return c(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.$pcAccordionPanel.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.$pcAccordion.tabindex,"aria-expanded":this.$pcAccordionPanel.active,"aria-controls":this.ariaControls,"data-pc-name":"accordionheader","data-p-disabled":this.$pcAccordionPanel.disabled,"data-p-active":this.$pcAccordionPanel.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.$pcAccordionPanel.active}}},dataP:function(){return _({active:this.$pcAccordionPanel.active})}},components:{ChevronUpIcon:I,ChevronDownIcon:E},directives:{ripple:N}};function J(e,n,t,o,d,a){var A=F("ripple");return e.asChild?h(e.$slots,"default",{key:1,class:f(e.cx("root")),active:a.$pcAccordionPanel.active,a11yAttrs:a.a11yAttrs,onClick:a.onClick}):C((i(),s(p(e.as),c({key:0,"data-p":a.dataP,class:e.cx("root"),onClick:a.onClick},a.attrs),{default:u(function(){return[h(e.$slots,"default",{active:a.$pcAccordionPanel.active}),h(e.$slots,"toggleicon",{active:a.$pcAccordionPanel.active,class:f(e.cx("toggleicon"))},function(){return[a.$pcAccordionPanel.active?(i(),s(p(a.$pcAccordion.$slots.collapseicon?a.$pcAccordion.$slots.collapseicon:a.$pcAccordion.collapseIcon?"span":"ChevronUpIcon"),c({key:0,class:[a.$pcAccordion.collapseIcon,e.cx("toggleicon")],"aria-hidden":"true"},e.ptm("toggleicon",a.ptParams)),null,16,["class"])):(i(),s(p(a.$pcAccordion.$slots.expandicon?a.$pcAccordion.$slots.expandicon:a.$pcAccordion.expandIcon?"span":"ChevronDownIcon"),c({key:1,class:[a.$pcAccordion.expandIcon,e.cx("toggleicon")],"aria-hidden":"true"},e.ptm("toggleicon",a.ptParams)),null,16,["class"]))]})]}),_:3},16,["data-p","class","onClick"])),[[A]])}K.render=J;var Y={root:function(n){var t=n.instance,o=n.props;return["p-accordionpanel",{"p-accordionpanel-active":t.active,"p-disabled":o.disabled}]}},Z=g.extend({name:"accordionpanel",classes:Y}),ee={name:"BaseAccordionPanel",extends:b,props:{value:{type:[String,Number],default:void 0},disabled:{type:Boolean,default:!1},as:{type:[String,Object],default:"DIV"},asChild:{type:Boolean,default:!1}},style:Z,provide:function(){return{$pcAccordionPanel:this,$parentInstance:this}}},x={name:"AccordionPanel",extends:ee,inheritAttrs:!1,inject:["$pcAccordion"],computed:{active:function(){return this.$pcAccordion.isItemActive(this.value)},attrs:function(){return c(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){return{"data-pc-name":"accordionpanel","data-p-disabled":this.disabled,"data-p-active":this.active}},ptParams:function(){return{context:{active:this.active}}}}};function ne(e,n,t,o,d,a){return e.asChild?h(e.$slots,"default",{key:1,class:f(e.cx("root")),active:a.active,a11yAttrs:a.a11yAttrs}):(i(),s(p(e.as),c({key:0,class:e.cx("root")},a.attrs),{default:u(function(){return[h(e.$slots,"default")]}),_:3},16,["class"]))}x.render=ne;var ae=`
    .p-accordionpanel {
        display: flex;
        flex-direction: column;
        border-style: solid;
        border-width: dt('accordion.panel.border.width');
        border-color: dt('accordion.panel.border.color');
    }

    .p-accordionheader {
        all: unset;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: dt('accordion.header.padding');
        color: dt('accordion.header.color');
        background: dt('accordion.header.background');
        border-style: solid;
        border-width: dt('accordion.header.border.width');
        border-color: dt('accordion.header.border.color');
        font-weight: dt('accordion.header.font.weight');
        border-radius: dt('accordion.header.border.radius');
        transition:
            background dt('accordion.transition.duration'),
            color dt('accordion.transition.duration'),
            outline-color dt('accordion.transition.duration'),
            box-shadow dt('accordion.transition.duration');
        outline-color: transparent;
    }

    .p-accordionpanel:first-child > .p-accordionheader {
        border-width: dt('accordion.header.first.border.width');
        border-start-start-radius: dt('accordion.header.first.top.border.radius');
        border-start-end-radius: dt('accordion.header.first.top.border.radius');
    }

    .p-accordionpanel:last-child > .p-accordionheader {
        border-end-start-radius: dt('accordion.header.last.bottom.border.radius');
        border-end-end-radius: dt('accordion.header.last.bottom.border.radius');
    }

    .p-accordionpanel:last-child.p-accordionpanel-active > .p-accordionheader {
        border-end-start-radius: dt('accordion.header.last.active.bottom.border.radius');
        border-end-end-radius: dt('accordion.header.last.active.bottom.border.radius');
    }

    .p-accordionheader-toggle-icon {
        color: dt('accordion.header.toggle.icon.color');
    }

    .p-accordionpanel:not(.p-disabled) .p-accordionheader:focus-visible {
        box-shadow: dt('accordion.header.focus.ring.shadow');
        outline: dt('accordion.header.focus.ring.width') dt('accordion.header.focus.ring.style') dt('accordion.header.focus.ring.color');
        outline-offset: dt('accordion.header.focus.ring.offset');
    }

    .p-accordionpanel:not(.p-accordionpanel-active):not(.p-disabled) > .p-accordionheader:hover {
        background: dt('accordion.header.hover.background');
        color: dt('accordion.header.hover.color');
    }

    .p-accordionpanel:not(.p-accordionpanel-active):not(.p-disabled) .p-accordionheader:hover .p-accordionheader-toggle-icon {
        color: dt('accordion.header.toggle.icon.hover.color');
    }

    .p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader {
        background: dt('accordion.header.active.background');
        color: dt('accordion.header.active.color');
    }

    .p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader .p-accordionheader-toggle-icon {
        color: dt('accordion.header.toggle.icon.active.color');
    }

    .p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader:hover {
        background: dt('accordion.header.active.hover.background');
        color: dt('accordion.header.active.hover.color');
    }

    .p-accordionpanel:not(.p-disabled).p-accordionpanel-active > .p-accordionheader:hover .p-accordionheader-toggle-icon {
        color: dt('accordion.header.toggle.icon.active.hover.color');
    }

    .p-accordioncontent {
        display: grid;
        grid-template-rows: 1fr;
    }

    .p-accordioncontent-wrapper {
        min-height: 0;
    }

    .p-accordioncontent-content {
        border-style: solid;
        border-width: dt('accordion.content.border.width');
        border-color: dt('accordion.content.border.color');
        background-color: dt('accordion.content.background');
        color: dt('accordion.content.color');
        padding: dt('accordion.content.padding');
    }
`,te={root:"p-accordion p-component"},oe=g.extend({name:"accordion",style:ae,classes:te}),re={name:"BaseAccordion",extends:b,props:{value:{type:[String,Number,Array],default:void 0},multiple:{type:Boolean,default:!1},lazy:{type:Boolean,default:!1},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1},expandIcon:{type:String,default:void 0},collapseIcon:{type:String,default:void 0},activeIndex:{type:[Number,Array],default:null}},style:oe,provide:function(){return{$pcAccordion:this,$parentInstance:this}}},ce={name:"Accordion",extends:re,inheritAttrs:!1,emits:["update:value","update:activeIndex","tab-open","tab-close","tab-click"],data:function(){return{d_value:this.value}},watch:{value:function(n){this.d_value=n},activeIndex:{immediate:!0,handler:function(n){this.hasAccordionTab&&(this.d_value=this.multiple?n==null?void 0:n.map(String):n==null?void 0:n.toString())}}},methods:{isItemActive:function(n){var t;return this.multiple?(t=this.d_value)===null||t===void 0?void 0:t.includes(n):this.d_value===n},updateValue:function(n){var t,o=this.isItemActive(n);this.multiple?o?this.d_value=this.d_value.filter(function(d){return d!==n}):this.d_value?this.d_value.push(n):this.d_value=[n]:this.d_value=o?null:n,this.$emit("update:value",this.d_value),this.$emit("update:activeIndex",this.multiple?(t=this.d_value)===null||t===void 0?void 0:t.map(Number):Number(this.d_value)),this.$emit(o?"tab-close":"tab-open",{originalEvent:void 0,index:Number(n)})},isAccordionTab:function(n){return n.type.name==="AccordionTab"},getTabProp:function(n,t){return n.props?n.props[t]:void 0},getKey:function(n,t){return this.getTabProp(n,"header")||t},getHeaderPT:function(n,t){var o=this;return{root:c({onClick:function(a){return o.onTabClick(a,t)}},this.getTabProp(n,"headerProps"),this.getTabPT(n,"header",t)),toggleicon:c(this.getTabProp(n,"headeractionprops"),this.getTabPT(n,"headeraction",t))}},getContentPT:function(n,t){return{root:c(this.getTabProp(n,"contentProps"),this.getTabPT(n,"toggleablecontent",t)),transition:this.getTabPT(n,"transition",t),content:this.getTabPT(n,"content",t)}},getTabPT:function(n,t,o){var d=this.tabs.length,a={props:n.props||{},parent:{instance:this,props:this.$props,state:this.$data},context:{index:o,count:d,first:o===0,last:o===d-1,active:this.isItemActive("".concat(o))}};return c(this.ptm("accordiontab.".concat(t),a),this.ptmo(this.getTabProp(n,"pt"),t,a))},onTabClick:function(n,t){this.$emit("tab-click",{originalEvent:n,index:t})}},computed:{tabs:function(){var n=this;return this.$slots.default().reduce(function(t,o){return n.isAccordionTab(o)?t.push(o):o.children&&o.children instanceof Array&&o.children.forEach(function(d){n.isAccordionTab(d)&&t.push(d)}),t},[])},hasAccordionTab:function(){return this.tabs.length}},components:{AccordionPanel:x,AccordionHeader:K,AccordionContent:w,ChevronUpIcon:I,ChevronRightIcon:V}};function ie(e,n,t,o,d,a){var A=P("AccordionHeader"),S=P("AccordionContent"),H=P("AccordionPanel");return i(),y("div",c({class:e.cx("root")},e.ptmi("root")),[a.hasAccordionTab?(i(!0),y(O,{key:0},L(a.tabs,function(r,l){return i(),s(H,{key:a.getKey(r,l),value:"".concat(l),pt:{root:a.getTabPT(r,"root",l)},disabled:a.getTabProp(r,"disabled")},{default:u(function(){return[k(A,{class:f(a.getTabProp(r,"headerClass")),pt:a.getHeaderPT(r,l)},{toggleicon:u(function(m){return[m.active?(i(),s(p(e.$slots.collapseicon?e.$slots.collapseicon:e.collapseIcon?"span":"ChevronDownIcon"),c({key:0,class:[e.collapseIcon,m.class],"aria-hidden":"true"},{ref_for:!0},a.getTabPT(r,"headericon",l)),null,16,["class"])):(i(),s(p(e.$slots.expandicon?e.$slots.expandicon:e.expandIcon?"span":"ChevronUpIcon"),c({key:1,class:[e.expandIcon,m.class],"aria-hidden":"true"},{ref_for:!0},a.getTabPT(r,"headericon",l)),null,16,["class"]))]}),default:u(function(){return[r.children&&r.children.headericon?(i(),s(p(r.children.headericon),{key:0,isTabActive:a.isItemActive("".concat(l)),active:a.isItemActive("".concat(l)),index:l},null,8,["isTabActive","active","index"])):v("",!0),r.props&&r.props.header?(i(),y("span",c({key:1,ref_for:!0},a.getTabPT(r,"headertitle",l)),z(r.props.header),17)):v("",!0),r.children&&r.children.header?(i(),s(p(r.children.header),{key:2})):v("",!0)]}),_:2},1032,["class","pt"]),k(S,{pt:a.getContentPT(r,l)},{default:u(function(){return[(i(),s(p(r)))]}),_:2},1032,["pt"])]}),_:2},1032,["value","pt","disabled"])}),128)):h(e.$slots,"default",{key:1})],16)}ce.render=ie;export{W as A,w as a,K as b,q as c,x as d,Z as e,oe as f,ce as s};
