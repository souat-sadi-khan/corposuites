<form class="ajax-form" method="POST" action="{{ route('admin.employee-types.store') }}">
    <div class="modal-header fm-modal-head">
        <div>
            <h5 class="modal-title">Employee Types</h5>
            <p>How to use </p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body fm-modal-body fm-body">
        <p><strong>Employee Types</strong> is used to categorize employees based on their employment arrangement, such as Permanent, Probation, Contract, Part-Time, Intern, or Consultant. It helps HR organize employees and apply appropriate HR policies.</p>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="80%">Field</th>
                    <th class="text-center">Required</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Name</strong> <br>
                        <small>The name of the employee type, such as Permanent, Contract, or Intern.</small>
                    </td>
                    <td class="text-center"><i class="ri-checkbox-circle-line text-success"></i></td>
                </tr>
                <tr>
                    <td>
                        <strong>Description</strong> <br>
                        <small>A short description explaining the purpose or nature of the employee type.</small>
                    </td>
                    <td class="text-center"><i class="ri-close-line text-danger"></i></td>
                </tr>
            </tbody>
        </table>

        <p>To use Employee Types, create the required types first and assign the appropriate type when adding or managing an employee. An Employee Type can be edited when necessary. Before deleting a type, make sure it is not assigned to any employee; otherwise, affected employee records may require reassignment to another appropriate type.</p>

    </div>

    <div class="modal-footer fm-modal-foot">
        <span class="fm-foot-note">
        </span>
        <div class="d-flex gap-2">
            <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
                <i class="ri-close-large-line me-1"></i> Cancel
            </button>
        </div>
    </div>
</form>
