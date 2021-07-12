define(
    [
        'jquery',
        'core/notification',
        'core/custom_interaction_events',
        'core/modal',
        'core/modal_registry',
        'core/templates',
        'core/ajax',
        'core/modal_events'
    ],
function (
    $,
    Notification,
    CustomEvents,
    Modal,
    ModalRegistry,
    Templates,
    Ajax,
    ModalEvents
) {

    var registered = false;
    var SELECTORS = {
        ANALYZE_BUTTON: '[data-action="analyze"]',
        MAJOR_FLAG: '[data-action="flagmajor"]',
        MINOR_FLAG: '[data-action="flagminor"]',
        CLEAR_FLAG: '[data-action="clear"]'
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalVideo = function (root) {
        this.attemptID = null;
        this.webroot = null;
        this.spinner = null;
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.ANALYZE_BUTTON).length) {
            Notification.exception({ message: 'No analyze button found' });
        }
        if (!this.getFooter().find(SELECTORS.MAJOR_FLAG).length) {
            Notification.exception({ message: 'No major flag button found' });
        }
        if (!this.getFooter().find(SELECTORS.MINOR_FLAG).length) {
            Notification.exception({ message: 'No minor flag button found' });
        }
        if (!this.getFooter().find(SELECTORS.CLEAR_FLAG).length) {
            Notification.exception({ message: 'No clear button found' });
        }
    };

    ModalVideo.TYPE = 'mod_quiz-view_video';
    ModalVideo.prototype = Object.create(Modal.prototype);
    ModalVideo.prototype.constructor = ModalVideo;

    /**
     * Make an AJAX request to increase/decrease violation severity on attempt
     * 
     * @param {int} severity severity of violation to add (negative to reduce)
     */
    ModalVideo.prototype.addViolations = function(severity) {
        let spinner = this.spinner;
        spinner.style.display = "block";
        var promises = Ajax.call([
            { methodname: 'mod_quiz_record_proctoring_violation', args: { attemptid: this.attemptID, severity: severity } },
        ]);

        promises[0].done(function (response) {
            spinner.style.display = "none";
            location.reload();
        }).fail(function (ex) {
            Notification.exception({ message: ex });
        });
    }

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalVideo.prototype.registerEventListeners = function () {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.ANALYZE_BUTTON, function (e, data) {
            // Send video to API for analysis then call the violation bus via ajax with results
            var videoURL = this.webroot + '/mod/quiz/serveproctorvideo.php?attemptid=' + this.attemptID;

            // Some API call and record the response

            // Take the result and update the database accordingly
            // this.addViolations(response);
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.MAJOR_FLAG, function (e, data) {
            this.addViolations(2);
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.MINOR_FLAG, function (e, data) {
            this.addViolations(1);
        }.bind(this));

        this.getModal().on(CustomEvents.events.activate, SELECTORS.CLEAR_FLAG, function (e, data) {
            this.addViolations(-10);
        }.bind(this));
    };

    /**
     * Override parent show function to update video source
     * 
     * @returns {void}
     */
    ModalVideo.prototype.show = function() {
        Modal.prototype.show.call(this);

        var player = document.getElementById('player');
        player.src = this.webroot + '/mod/quiz/serveproctorvideo.php?stream=true&&attemptid=' + this.attemptID;

        this.spinner = document.getElementById('loading-spinner');

        this.getRoot().on(ModalEvents.hidden, function () {
            player.pause();
        });
    }

    /**
     * Set the id of the attempt to pull the video from
     * 
     * @param {int} id
     */
    ModalVideo.prototype.setAttemptID = function (id) {
        this.attemptID = id;
    };

    /**
     * Set the webroot for the server
     * 
     * @param {string} webroot 
     */
    ModalVideo.prototype.setWebroot = function (webroot) {
        this.webroot = webroot;
    };

    // Automatically register with the modal registry the first time this module is imported
    if (!registered) {
        ModalRegistry.register(ModalVideo.TYPE, ModalVideo, 'mod_quiz/modal_view_video');
        registered = true;
    }

    return ModalVideo;
});
