var auditTable;
var selectedLogs = new Set();



var AuditLogTable = function(){



    var init = function(){


        if(!$().DataTable){

            console.warn(
                'DataTables not loaded'
            );

            return;

        }



        auditTable = $('#auditTable').DataTable({


            dom:'t',


            processing:true,


            serverSide:true,


            pageLength:10,


            lengthChange:false,


            searching:true,


            order:[
                [7,'desc']
            ],



            ajax:{


                url:
                $('#auditTable').data('url'),



                data:function(d){


                    d.search =
                    $('#logSearch').val();



                    d.actor_id =
                    $('#actor_id').val();



                    d.module =
                    $('#module').val();



                    d.action =
                    $('#action').val();



                }

            },




            columns:[


                {

                    data:'id',

                    orderable:false,

                    searchable:false,


                    className:
                    'tl-check-col',


                    render:function(data){


                        let checked =
                        selectedLogs.has(
                            Number(data)
                        )
                        ?
                        'checked'
                        :
                        '';



                        return `


                        <input

                        type="checkbox"

                        class="audit-row-check"

                        data-id="${data}"

                        ${checked}

                        >

                        `;

                    }

                },



                {

                    data:'actor'

                },



                {

                    data:'module',

                    render:function(data){

                        return `

                        <span class="badge bg-light text-dark">

                        ${data}

                        </span>

                        `;

                    }

                },



                {

                    data:'action_badge',

                    orderable:false

                },



                {

                    data:'description',

                    render:function(data){

                        return data ?? '-';

                    }

                },



                {

                    data:'target'

                },



                {

                    data:'ip_address'

                },



                {

                    data:'created_at'

                },



                {

                    data:'action',

                    orderable:false,

                    searchable:false,

                    className:
                    'text-end'

                }


            ],





            language:{


                emptyTable:`


                <div class="text-center py-4">


                <img

                src="${window.location.origin}/assets/images/nothing-to-show.svg"

                class="img-fluid mb-2"

                style="max-width:150px">


                <p class="text-muted mb-0">

                No activity found

                </p>


                </div>


                `


            },




            drawCallback:function(){



                syncSelection();


                updatePagination();


                if(
                    typeof _componentRemoteOffcanvasLoadAfterAjax
                    ===
                    'function'
                ){

                    _componentRemoteOffcanvasLoadAfterAjax();

                }


            }



        });



    };





    return {


        init:function(){

            init();

        }


    };



}();





// ================================
// Pagination
// ================================


function updatePagination(){


    var info =
    auditTable.page.info();



    var start =
    info.recordsDisplay === 0
    ?
    0
    :
    info.start + 1;



    $('#tlInfo').text(

        start
        +
        ' - '
        +
        info.end
        +
        ' of '
        +
        info.recordsDisplay

    );





    $('#tlPrev')
    .prop(
        'disabled',
        info.page === 0
    );



    $('#tlNext')
    .prop(
        'disabled',

        info.page >= info.pages - 1
        ||
        info.pages === 0

    );



}







// ================================
// Selection
// ================================


function syncSelection(){



    var ids=[];



    auditTable
    .rows({
        page:'current'
    })
    .every(function(){


        ids.push(
            Number(
                this.data().id
            )
        );


    });





    $('.audit-row-check')
    .each(function(){


        let id =
        Number(
            $(this).data('id')
        );



        let checked =
        selectedLogs.has(id);



        $(this)
        .prop(
            'checked',
            checked
        );



        $(this)
        .closest('tr')
        .toggleClass(
            'tl-row-selected',
            checked
        );



    });




    let allChecked =
    ids.length > 0
    &&
    ids.every(function(id){

        return selectedLogs.has(id);

    });




    $('#selectAllChk')
    .prop(
        'checked',
        allChecked
    );


}







// ================================
// Expand Detail
// ================================


function renderAuditDetail(row){



return `


<div class="tl-detail">


<div class="tl-detail-col">


<h4>
Request
</h4>


<div class="tl-detail-row">

<i class="ri-global-line"></i>

${row.ip_address ?? '-'}

</div>



<div class="tl-detail-row">

<i class="ri-link"></i>

${row.url ?? '-'}

</div>


</div>





<div class="tl-detail-col">


<h4>
Model
</h4>


<div class="tl-detail-row">

<i class="ri-database-line"></i>


${row.model ?? '-'}


</div>


</div>



</div>


`;



}







// ================================
// Document Ready
// ================================


document.addEventListener(
'DOMContentLoaded',
function(){



AuditLogTable.init();





// Search


$('#logSearch')
.on(
'keyup',
function(){

    auditTable.draw();

});






// Filters


$('#actor_id,#module,#action')
.on(
'change',
function(){

    auditTable.draw();

});






// Previous


$('#tlPrev')
.on(
'click',
function(){


auditTable
.page('previous')
.draw('page');


});







// Next


$('#tlNext')
.on(
'click',
function(){


auditTable
.page('next')
.draw('page');


});








// Checkbox


$('#auditTable tbody')
.on(
'change',
'.audit-row-check',
function(){



let id =
Number(
$(this).data('id')
);



if(this.checked){

    selectedLogs.add(id);

}
else{

    selectedLogs.delete(id);

}




$(this)
.closest('tr')
.toggleClass(
'tl-row-selected',
this.checked
);



syncSelection();



});







// Select All


$('#selectAllChk')
.on(
'change',
function(){



let checked =
this.checked;



auditTable
.rows({
page:'current'
})
.every(function(){



let id =
Number(
this.data().id
);



if(checked){

selectedLogs.add(id);

}
else{

selectedLogs.delete(id);

}



});



syncSelection();



});








// Expand


$('#auditTable tbody')
.on(
'click',
'.tl-expand-btn',
function(){



let btn=$(this);

let tr =
btn.closest('tr');


let row =
auditTable.row(tr);




if(row.child.isShown()){


row.child.hide();


tr.removeClass(
'tl-row-expanded'
);



btn.removeClass(
'is-open'
);



}
else{


row.child(
renderAuditDetail(row.data())
)
.show();



tr.addClass(
'tl-row-expanded'
);



btn.addClass(
'is-open'
);



}




});







// Filter dropdown


$('#logFilterBtn')
.on(
'click',
function(e){


e.stopPropagation();


$('#logFilterDd')
.toggleClass(
'is-open'
);


});





$('#logFilterDd')
.on(
'click',
function(e){

e.stopPropagation();

});





$(document)
.on(
'click',
function(){


$('#logFilterDd')
.removeClass(
'is-open'
);


});





});
