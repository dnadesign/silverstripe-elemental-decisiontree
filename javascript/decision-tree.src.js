/**
 * Decision Tree - Accessible Interactive Component
 *
 * Vanilla JS conversion of original jQuery implementation.
 * Adds ARIA live region announcements for screen readers.
 */
(function() {
    'use strict';

    /**
     * Announce a message to screen readers via the live region
     * @param {HTMLElement} tree - The decision tree container
     * @param {string} message - Message to announce
     */
    function announce(tree, message) {
        const announcer = tree.querySelector('.decisiontree-announcer');
        if (announcer) {
            // Clear first, then set - ensures announcement even if same text
            announcer.textContent = '';
            // Small delay ensures screen readers pick up the change
            setTimeout(() => {
                announcer.textContent = message;
            }, 50);
        }
    }

    /**
     * Extract the question/result title from step HTML for announcements
     * @param {HTMLElement} stepElement - The step element
     * @returns {string} - The title text or empty string
     */
    function getStepTitle(step) {
        const titleInner = step.querySelector('.step-title-inner');
        if (titleInner) {
            return titleInner.textContent.trim();
        }
        const resultTitle = step.querySelector('.step-title');
        if (resultTitle) {
            return resultTitle.textContent.trim();
        }
        return '';
    }

    /**
     * Check if the loaded step is a result (final answer)
     */
    function isResultStep(step) {
        return step.classList.contains('step--result') ||
                step.getAttribute('data-step-type') === 'result';
    }

    /**
     * Serialize form data to URL-encoded string
     */
    function serializeForm(form) {
        var formData = new FormData(form);
        var params = new URLSearchParams();
        formData.forEach(function(value, key) {
            params.append(key, value);
        });
        return params.toString();
    }

    /**
     * Fade out element then execute callback
     */
    function fadeOut(element, callback) {
        element.style.transition = 'opacity 0.4s ease';
        element.style.opacity = '0';
        setTimeout(function() {
            element.style.display = 'none';
            if (callback) callback();
            element.style.display = '';
            element.style.opacity = '';
            element.style.transition = '';
        }, 400);
    }

    /**
     * Smooth scroll to element with offset
     */
    function scrollToElement(element, offset, callback) {
        var rect = element.getBoundingClientRect();
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        var targetTop = rect.top + scrollTop - offset;

        window.scrollTo({
            top: targetTop,
            behavior: 'smooth'
        });

        if (callback) {
            setTimeout(callback, 500);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        /**
         * Submit Step form when selecting an answer
         * Adds loading animation to empty "nextstep" div
         * Fetch content via ajax
         * Insert HTML and update URL
         */
        document.addEventListener('change', function(event) {
            var input = event.target;
            if (input.name !== 'stepanswerid' || input.type !== 'radio') {
                return;
            }

            var form = input.closest('form');
            if (!form) return;

            var step = form.parentElement;
            if (!step || !step.classList.contains('step')) return;

            var nextstepHolder = step.querySelector(':scope > .nextstep');
            if (!nextstepHolder) return;

            var tree = step.closest('.decisiontree');

            // Insert spinner HTML
            nextstepHolder.innerHTML = '<div class="spinner-holder"><div class="spinner"><span class="sr-only">loading</span></div></div>';

            // Announce loading to screen readers
            announce(tree, 'Loading next question, please wait.');

            // After 100ms, add loading class (matches original jQuery timing)
            setTimeout(function() {
                nextstepHolder.classList.add('loading');
            }, 100);

            // Make AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open(form.getAttribute('method') || 'POST', form.getAttribute('action'), true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);

                        // Add new-content-loaded class, insert HTML, update URL (matches original)
                        nextstepHolder.classList.add('new-content-loaded');
                        nextstepHolder.innerHTML = data.html;
                        window.history.pushState(null, null, data.nexturl);

                        // Announce the new content to screen readers
                        var newStep = nextstepHolder.querySelector('.step');
                        if (newStep) {
                            var title = getStepTitle(newStep);
                            if (isResultStep(newStep)) {
                                announce(tree, 'Result: ' + title);
                            } else {
                                var stepNumber = newStep.querySelector('.step-number');
                                var questionNum = stepNumber ? stepNumber.textContent.trim() : '';
                                announce(tree, 'Question ' + questionNum + ' ' + title);
                            }
                        }
                    } catch (e) {
                        nextstepHolder.innerHTML = xhr.responseText;
                        announce(tree, 'Error loading question. Please reload the page and try again.');
                    }
                } else {
                    nextstepHolder.innerHTML = xhr.responseText;
                    announce(tree, 'Error loading question. Please reload the page and try again.');
                }

                // Always: after 100ms remove both classes (matches original jQuery .always())
                setTimeout(function() {
                    nextstepHolder.classList.remove('loading', 'new-content-loaded');
                }, 100);
            };

            xhr.onerror = function() {
                nextstepHolder.innerHTML = '<p>An error occurred. Please try again.</p>';
                announce(tree, 'Error loading question. Please reload the page and try again.');

                setTimeout(function() {
                    nextstepHolder.classList.remove('loading', 'new-content-loaded');
                }, 100);
            };

            xhr.send(serializeForm(form));
        });

        /**
         * Handles the restart button
         * Empties all subsequent steps then
         * Scroll back to first step then
         * Reset url to original page url
         */
        document.addEventListener('click', function(event) {
            var button = event.target;
            if (button.getAttribute('data-action') !== 'restart-tree') {
                return;
            }

            var firststep = button.closest('.step--first');
            if (!firststep) return;

            var radio = firststep.querySelectorAll('input[type="radio"]');
            var tree = firststep.closest('.decisiontree');
            var firstLegend = firststep.querySelector('legend.step-legend');
            var nextstepHolder = firststep.querySelector(':scope > .nextstep');

            if (nextstepHolder) {
                // Fade out then clear content (matches original jQuery fadeOut)
                fadeOut(nextstepHolder, function() {
                    nextstepHolder.innerHTML = '';

                    // Uncheck all radios
                    radio.forEach(function(r) {
                        r.checked = false;
                    });

                    // Announce reset to screen readers
                    announce(tree, 'Decision tree reset. Starting from the first question.');

                    if (firstLegend) {
                        // Scroll to the first question's legend
                        scrollToElement(firstLegend, 150, function() {
                            // Set focus on the legend for keyboard navigation
                            if (!firstLegend.getAttribute('tabindex')) {
                                firstLegend.setAttribute('tabindex', '-1');
                            }
                            firstLegend.focus();
                        });
                    } else if (tree) {
                        scrollToElement(tree, 150);
                    }

                    // Reset URL
                    var url = location.protocol + '//' + location.host + location.pathname;
                    window.history.pushState(null, null, url);
                });
            }
        });
    });
})();
