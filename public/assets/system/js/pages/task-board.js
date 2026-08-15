// =====================================================
// Task Board Kanban
//
// Vanilla HTML5 drag-and-drop, no new library — the same
// "no new dependency for one screen" choice the Sales
// Pipeline Kanban made. Cards move optimistically and roll
// back if the server rejects the move, so the board can
// never quietly disagree with the database.
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    var board = document.getElementById('taskBoard');
    if (!board) {
        return;
    }

    var toast = document.getElementById('taskBoardToast');
    var toastTimer = null;
    var dragged = null;

    function notify(message, isError) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.classList.add('is-visible');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2600);
    }

    function refreshCounts() {
        board.querySelectorAll('.tb-column').forEach(function (column) {
            var body = column.querySelector('.tb-column-body');
            var cards = body.querySelectorAll('.tb-card').length;
            var count = column.querySelector('.tb-count');
            var empty = body.querySelector('.tb-empty');

            if (count) {
                count.textContent = cards;
            }

            if (cards === 0 && !empty) {
                var placeholder = document.createElement('p');
                placeholder.className = 'tb-empty';
                placeholder.textContent = 'Nothing here yet';
                body.appendChild(placeholder);
            } else if (cards > 0 && empty) {
                empty.remove();
            }
        });
    }

    // Ids of the column's cards, top to bottom — sent so the server can
    // renumber sort_order to match what the user actually sees.
    function orderedIds(body) {
        return Array.prototype.map.call(
            body.querySelectorAll('.tb-card'),
            function (card) { return card.getAttribute('data-id'); }
        );
    }

    // Where the card should land relative to the cards already in the column.
    function cardAfterPoint(body, y) {
        var candidates = Array.prototype.slice.call(
            body.querySelectorAll('.tb-card:not(.is-dragging)')
        );

        return candidates.reduce(function (closest, card) {
            var box = card.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: card };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    function bindCard(card) {
        card.addEventListener('dragstart', function () {
            dragged = card;
            card.classList.add('is-dragging');
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('is-dragging');
            dragged = null;
        });
    }

    board.querySelectorAll('.tb-card').forEach(bindCard);

    board.querySelectorAll('.tb-column-body').forEach(function (body) {
        body.addEventListener('dragover', function (e) {
            e.preventDefault();
            body.classList.add('is-over');

            if (!dragged) {
                return;
            }

            var reference = cardAfterPoint(body, e.clientY);

            if (reference) {
                body.insertBefore(dragged, reference);
            } else {
                body.appendChild(dragged);
            }
        });

        body.addEventListener('dragleave', function (e) {
            if (!body.contains(e.relatedTarget)) {
                body.classList.remove('is-over');
            }
        });

        body.addEventListener('drop', function (e) {
            e.preventDefault();
            body.classList.remove('is-over');

            var card = dragged;
            if (!card) {
                return;
            }

            var origin = card.getAttribute('data-origin-body');
            var newStatus = body.getAttribute('data-status');
            var originStatus = card.getAttribute('data-status-before') || null;

            refreshCounts();
            persist(card, body, newStatus, origin, originStatus);
        });
    });

    // Remember where a card came from, so a failed save can put it back.
    board.addEventListener('dragstart', function (e) {
        var card = e.target.closest ? e.target.closest('.tb-card') : null;
        if (!card) {
            return;
        }

        var body = card.parentElement;
        card.__originBody = body;
        card.__originNext = card.nextElementSibling;
        card.setAttribute('data-status-before', body.getAttribute('data-status'));
    }, true);

    function persist(card, body, newStatus, _origin, originStatus) {
        if (newStatus === originStatus && !card.__moved) {
            // Same column: still worth saving, the order may have changed.
        }

        var url = window.taskBoardMoveUrlTemplate.replace('__ID__', card.getAttribute('data-id'));
        card.classList.add('is-saving');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                task_status: newStatus,
                ordered_ids: orderedIds(body)
            }
        }).done(function (response) {
            card.classList.remove('is-saving');

            if (!response || response.status !== true) {
                rollback(card, response && response.message);
                return;
            }

            // Reflect the server's own progress figure (dropping into Done
            // takes a task to 100%).
            var progress = card.querySelector('.tb-progress span');
            if (response.progress_percent !== undefined) {
                if (progress) {
                    progress.style.width = response.progress_percent + '%';
                } else if (response.progress_percent > 0) {
                    var bar = document.createElement('div');
                    bar.className = 'tb-progress';
                    bar.innerHTML = '<span style="width:' + response.progress_percent + '%"></span>';
                    card.insertBefore(bar, card.querySelector('.tb-card-foot'));
                }
            }

            notify(response.message || 'Task moved.');
        }).fail(function (xhr) {
            card.classList.remove('is-saving');
            rollback(card, xhr.responseJSON && xhr.responseJSON.message);
        });
    }

    function rollback(card, message) {
        if (card.__originBody) {
            if (card.__originNext && card.__originNext.parentElement === card.__originBody) {
                card.__originBody.insertBefore(card, card.__originNext);
            } else {
                card.__originBody.appendChild(card);
            }
        }

        refreshCounts();
        notify(message || 'Could not move that task — put it back.', true);
    }
});
