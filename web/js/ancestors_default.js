$(document).ready(function() {
    $("select").select2();

    $(document).foundation();
    
    
    $("input[type=date]").datepicker({
        yearRange: "-500:+0",
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd'
    });
});