document.addEventListener('DOMContentLoaded', function () {
    var board = document.getElementById('kanbanBoard');
    if (!board) {
        return;
    }

    var draggedCard = null;

    board.querySelectorAll('.kanban-card').forEach(function (card) {
        card.addEventListener('dragstart', function () {
            draggedCard = card;
            card.classList.add('dragging');
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
            draggedCard = null;
        });
    });

    board.querySelectorAll('.kanban-column-body').forEach(function (column) {
        column.addEventListener('dragover', function (e) {
            e.preventDefault();
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', function () {
            column.classList.remove('drag-over');
        });

        column.addEventListener('drop', function (e) {
            e.preventDefault();
            column.classList.remove('drag-over');

            if (!draggedCard) {
                return;
            }

            var opportunityId = draggedCard.getAttribute('data-id');
            var newStage = column.getAttribute('data-stage');
            var oldColumn = draggedCard.parentElement;

            if (oldColumn === column) {
                return;
            }

            column.appendChild(draggedCard);

            var url = window.opportunityMoveStageUrlTemplate.replace('__ID__', opportunityId);

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    stage: newStage,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (!response.success) {
                        oldColumn.appendChild(draggedCard);
                        alert(response.message || 'Failed to update stage.');
                    }
                },
                error: function () {
                    oldColumn.appendChild(draggedCard);
                    alert('Failed to update stage.');
                }
            });
        });
    });
});
