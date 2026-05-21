{{-- Tawk.to live chat widget. Renders only when services.tawkto.widget_url is configured. --}}
@if(config('services.tawkto.widget_url'))
<script type="text/javascript">
var Tawk_API=Tawk_API||{};
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='{{ config('services.tawkto.widget_url') }}';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
@endif
