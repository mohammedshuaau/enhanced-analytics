import{m as e}from"./alpine-CLWRBzRS.js";console.log("[Consent Banner] Script loading...");window.Alpine||(window.Alpine=e,e.start(),console.log("[Consent Banner] Alpine.js initialized"));e.data("consentBanner",()=>({show:!0,showSettings:!1,settings:{geolocation:!0},init(){if(console.log("[Consent Banner] Component initialized"),localStorage.getItem("analytics_consent")){this.show=!1;return}const n=localStorage.getItem("analytics_settings");n&&(this.settings=JSON.parse(n)),console.log("Initial settings:",this.settings)},toggleGeolocation(){console.log("Toggling geolocation"),this.settings.geolocation=!this.settings.geolocation,console.log("New geolocation value:",this.settings.geolocation)},accept(){this.saveConsent(!0),this.show=!1},decline(){this.saveConsent(!1),this.show=!1},toggleSettings(){this.showSettings=!this.showSettings,console.log("Settings panel:",this.showSettings?"shown":"hidden")},saveConsent(t){console.log("Saving consent:",t,"with settings:",this.settings),localStorage.setItem("analytics_consent",t?"accepted":"declined"),localStorage.setItem("analytics_settings",JSON.stringify(this.settings)),fetch("/enhanced-analytics/consent",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').getAttribute("content")},body:JSON.stringify({consent:t,settings:this.settings})}).catch(n=>console.error("Error saving consent:",n)),window.dispatchEvent(new CustomEvent("analytics-consent-changed",{detail:{consent:t,settings:this.settings}}))}}));
