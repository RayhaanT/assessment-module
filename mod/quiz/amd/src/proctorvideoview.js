define(
    [
        'jquery',
        'core/templates',
        'core/modal_factory',
        'mod_quiz/modal_view_video',
        'core/modal_events'
    ],
function (
    $,
    Templates,
    ModalFactory,
    ModalViewer,
    ModalEvents
) {

    return {

        init: function(webroot) {
            var trigger = $('[data-action="videoviewer"]');

            ModalFactory.create(
                {
                    type: ModalViewer.TYPE,
                    large: true,
                    preShowCallback: function (triggerElement, modal) {
                        triggerElement = $(triggerElement);
                        modal.setAttemptID(triggerElement.attr('data-attemptid'));
                        modal.setWebroot(webroot);
                    }
                },
                trigger);
            }
        }
});