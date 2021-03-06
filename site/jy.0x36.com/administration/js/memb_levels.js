/***************************************************************************
 *                            Dolphin Web Community Software
 *                              -------------------
 *     begin                : Mon Mar 23 2006
 *     copyright            : (C) 2007 BoonEx Group
 *     website              : http://www.boonex.com
 *
 *
 *
 ****************************************************************************/

/***************************************************************************
 *
 *   This is a free software; you can modify it under the terms of BoonEx
 *   Product License Agreement published on BoonEx site at http://www.boonex.com/downloads/license.pdf
 *   You may not however distribute it for free or/and a fee.
 *   This notice may not be removed from the source code. You may not also remove any other visible
 *   reference and links to BoonEx Group as provided in source code.
 *
 ***************************************************************************/

function onEditAction(iLevelId, iActionId) {
    $.post(
        sAdminUrl + 'memb_levels.php',
        {action: 'get_edit_form_action', level_id: iLevelId, action_id: iActionId},
        function(oResult) {
            $('#adm-mlevels-holder').html(oResult.code).show();
            $('#adm-mlevels-holder > #adm-mlevels-action').dolPopup({
                fog: {
                    color: '#fff', 
                    opacity: .7
                },
                closeOnOuterClick: false
            });

            $(document).addWebForms();
            $("input[type='datetime']", document).each(function() {
            	$(this).dynDateTime({
            		ifFormat: '%Y-%m-%d %H:%M:%S',
                    showsTime: true,
                    position_css: 'fixed'
                });
            });
        },
        'json'
    );
}
function onResult(oResult) {
    var sContentKey = '#adm-mlevels-action-content';

    if(parseInt(oResult.code) == 0) {
        $(sContentKey + ' > form').bx_anim('hide', 'fade', 'slow', function() {
            $(sContentKey).prepend(oResult.message);
            setTimeout("$('" + sContentKey + " > :first').bx_anim('hide', 'fade', 'slow', function(){$(this).remove();$('" + sContentKey + " > form').bx_anim('show', 'fade', 'slow', function(){$('#adm-mlevels-action').dolPopupHide({});});})", 3000);
        });
    }
    else {
        $(sContentKey + ' > form').bx_anim('hide', 'fade', 'slow', function() {
            $(sContentKey).prepend(oResult.message);
            setTimeout("$('" + sContentKey + " > :first').bx_anim('hide', 'fade', 'slow', function(){$(this).remove();$('" + sContentKey + " > form').bx_anim('show', 'fade', 'slow');})", 3000);
        });
    }
}