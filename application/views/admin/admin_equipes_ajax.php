<script type="text/javascript">

var save_method; //for save method string
var table;
var base_url = '<?php echo base_url();?>';
var reserve = [];


$(document).ready(function() {

    //Initialisation de la table réserve (modal)
    table_reserve = $("#modal_table_reserve").DataTable({
        "columnDefs": [
            {
                "targets": [ 4 ],
                "visible": false,
                "searchable": false,
                "never": true
            }
        ],
        "bFilter": false,
        "paging":   false,
        "ordering": false,
        data: reserve
    });

    //Retrait du joueur lors d'un clic sur la ligne et ajout dans la table déterminée
    table_reserve.on('click', 'tbody tr' ,function() {
        var id_table = $("input[id=id_table]").val();//id de la table dans laquelle on ajoute le joueur
        if(id_table != null)
        {
            table = $("#"+id_table).DataTable();
            //count rows in table
            //if rows == 4 do nothing
            if(table.rows().count() < 4)
            {
                var $row = $(this);
                var addRow = table_reserve.row($row);
                table.row.add(addRow.data()).draw();
                addRow.remove().draw();
                $("#reserve-modal").modal('hide');
            }
            else //montrer l'erreur
            {
                $('#alert-modal').show();
            }   
        }
    });

    //Remplissage de l'input select
    get_interclubs(function(interclub_data){
        for (i = 0; i < interclub_data.interclub.length; i++)
        {
            $('#select-interclub').append('<option value="'+interclub_data.interclub[i].id_interclub+'">'+interclub_data.interclub[i].date+'</option>');
        }

        if(get_itemLocal_beginWith('te') > 0)//Si il y a des équipes dans le localStorage, on charge les equipes avec le localStorage
        {
            $('#select-interclub option:last').prop('selected', true);
            $('table').DataTable().destroy();
            $('div[id^="div"]').remove();
            load_teams_local();
        }
        else //Sinon sélectionner le dernier interclub et charger les équipes à partir de la DB
        {
            $('#select-interclub option:last').prop('selected', true);
            load_teams($('#select-interclub').val());
        }
    });

    //Lors de la sélection de l'interclub, charger les équipes liés à celui-ci
    $('#select-interclub').change(function() {
        $('#alert').empty();
        $('#alert').hide();
        $('table').DataTable().destroy();
        $('div[id^="div"]').remove();
        load_teams($(this).val());
    });

    //Insérer les équipes dans la DB
    $('#apply_teams').on('click', function(){
        var datas = {};
        var tables = [];
        //Pour chaque table sur la page, récupérer les données sous forme d'objets
        $('table').each(function (index) {
            var obj = tableToObj($(this).attr('id'));
            tables[index] = obj;
        });
        datas.interclub = $('#select-interclub').val();
        datas.tables = tables;
        $.ajax({
            type: 'POST',
            url: "<?php echo site_url('Rencontre/ajax_add')?>",
            data: {json: JSON.stringify(datas) },
            dataType: 'json',
            success: function(data)
            {
                if(data.status) 
                {
                    $('#alert').empty();
                    $("#alert").removeClass('alert-danger');
                    $("#alert").addClass('alert-success');
                    $("#alert").append('<p class="text-center"><strong>Les équipes ont été créées pour cet interclub</strong></p>');
                    $("#alert").show();
                }                    
                else
                {
                    $('#alert').empty();
                    $("#alert").addClass('alert-danger');
                    $("#alert").append('<p class="text-center"><strong>Les équipes ont déjà été générées pour cet interclub</strong></p>'); 
                    $("#alert").show();   
                }
            },
            error: function (jqXHR, textStatus, errorThrown)
            {
                return null;
            }
        });
    });
    

});

//Transforme données d'une table en un objet
function tableToObj(tableId)
{
    // Loop through grabbing everything
    var myRows = [];
    var $headers = $("th");
    var $rows = $("#"+ tableId +" tbody tr").each(function(index) {
      $cells = $(this).find("td");
      myRows[index] = {};
      myRows[index][$($headers[0]).html()] = $($cells[0]).html();
      /*$cells.each(function(cellIndex) {
      myRows[index][$($headers[cellIndex]).html()] = $(this).html();        
      });*/    
    });

    // Let's put this in the object like you want and convert to JSON (Note: jQuery will also do this for you on the Ajax request)
    var myObj = {};
    myObj.joueurs = myRows;
    myObj.equipe = tableId.slice(-2);
    return myObj;
}

//Montrer les joueurs réserves et initialiser variable de table correspondante
function select_joueur(id_table)
{
    $("input[id=id_table]").attr('value',id_table);
    $('#alert-modal').hide();
    $("#reserve-modal").modal();
}

//Vider le localStorage
function clear_localStorage()
{
    for (var a in localStorage) {
        localStorage.removeItem(a);
    }
}

//Compter le nombre de données dont la clé contient 'str' dans le localStorage
function get_itemLocal_beginWith(str)
{
    var cpt = 0;
    for (i = 0; i < localStorage.length; i++) {
        key = localStorage.key(i);
        if (key.indexOf(str) !== -1) {
            cpt++;
        }
    }
    return cpt
}

function reload_table()
{
    table.ajax.reload(null,false); //reload datatable ajax 
}

//Ajouter une table
function add_table(idDivision)
{
    //Déterminer le nom de l'équipe en fonction des équipes déjà existantes
    equipe = $( "#div"+idDivision+" table:last" ).attr('id');
    if(equipe != null) char = nextChar(equipe.substr(equipe.length - 1));
    else char = 'A';
    var cpt = $('#div'+idDivision).children('table').length;
    var css = ((cpt%2) == 0)? " float-left" : "";
    var id_table = "table_team" + idDivision + char;
    var $table = $( '<div class="col-md-4 inline-table'+css+'"><h2>' + char + '</h2><table id="' + id_table + '" class="table table-striped table-bordered" cellspacing="0" width="100%""><thead><tr><th>N°</th><th>Nom</th><th>Prenom</th><th>Class</th><th>Action</th></tr></thead><tbody></tbody></table><div class="col-md-12"><button class="float-left" onclick="select_joueur(\''+id_table+'\')">Ajouter un joueur</button><button class="float-right clear-fix" onclick="delete_table(\''+id_table+'\')">Supprimer l\'équipe</button></div></div>' );
    $("#div" + idDivision).append($table);

    table = $('#'+id_table).DataTable({
        "columnDefs": [
            {
                "data": null,
                "defaultContent": "<input type='button' value='Retirer'>",
                "targets": 4
            }
        ],
        "bFilter": false,
        "paging":   false,
        "ordering": false,
        "info":     false,
        "responsive": true,
        data:""
    });
    $('#'+id_table + ' tbody').on( 'click', 'input[type="button"]', function () {
    var table = $(this).parents('table').DataTable();
    var row = table.row( $(this).parents('tr') );
    var rowNode = row.node();
    row.remove();
    table_reserve.row.add( rowNode ).draw();
    });
}

function delete_table(idTable)
{
    table = $('#'+idTable).DataTable();
    if($('#'+idTable+" tr").length > 0) //Si la table contient des éléments, d'abord la vider
    {
        table.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
            var rowNode = this.node();
            table_reserve.row.add( rowNode ).draw();
        } );
    }
    $('#'+idTable+'_wrapper').closest('div .inline-table').remove();
}

//Charger les équipes à partir du contenu de la DB en fonction d'un Interclub
function load_teams(interclub)
{
    get_divisions(function(division_data){
        division_data.divisions.forEach(function(division){     
            $("#main").append('<div id="div' + division.id_division + '" class="container-fluid clear-fix division"><h2>' + division.nom + '</h2></div>');
            get_equipes(division.id_division, function(equipe_data){
                equipe_data.equipes.forEach(function(equipe){           
                    var i = 0;
                    var css = ((i%2) == 0)? " float-left" : "";
                    var id_table = "table_team" + division.id_division + equipe.nom;
                    var $table = $( '<div class="col-md-4 inline-table'+css+'"><h2>' + equipe.nom + '</h2><table id="' + id_table + '" class="table table-striped table-bordered" cellspacing="0" width="100%""><thead><tr><th>N°</th><th>Nom</th><th>Prenom</th><th>Class</th><th>Action</th></tr></thead><tbody></tbody></table></div>' );
                    $("#div" + division.id_division).append($table);

                    //datatables
                    table = $('#table_team' + division.id_division + equipe.nom).DataTable({
                        "bFilter": false,
                        "paging":   false,
                        "ordering": false,
                        "info":     false,
                        "responsive": true,
                        // Load data for the table's content from an Ajax source
                        "ajax": "<?php echo site_url()?>rencontre/ajax_joueurs_team/" + interclub + "/" + equipe.id_equipe,
                        fnDrawCallback: function () {
                            var rows = this.fnGetData();
                            if ( rows.length === 0 ) {
                                if( localStorage.length === 0 )
                                {
                                    if ($("#alert").is(':empty')){
                                        $("#alert").addClass('alert-danger');
                                        var url = "<?php echo site_url('Home/Joueurs')?>";
                                        $("#alert").append('<p class="text-center"><strong>Les équipes sont vides pour cet interclub.  Vous pouvez les générer sur <a href='+url+'>cette page</a></strong></p>');
                                        $("#alert").show();
                                    }
                                }
                                /*else
                                {
                                    $('table').DataTable().destroy();
                                    $('div[id^="div"]').remove();
                                    load_teams_local();
                                }*/
                            }
                            else 
                            {
                                $('#alert').empty();
                                $('#alert').removeClass('alert-danger');
                                //$('#alert').hide();
                            }
                        }
                    });
                    i++;
                    $('#table_team' + division.id_division + equipe.nom + ' tbody').on( 'click', 'input[type="button"]', function () {
                        var table = $(this).parents('table').DataTable();
                        var row = table.row( $(this).parents('tr') );
                        var rowNode = row.node();
                        row.remove();
                        table_reserve.row.add( rowNode ).draw();
                    });
                });
            });
        })
    });
}

//Charger les équipes à partir de la localStorage
function load_teams_local()
{
    get_divisions(function(division_data){
        division_data.divisions.forEach(function(division){
            $("#main").append('<div id="div' + division.id_division + '" class="container-fluid clear-fix division"><h2>' + division.nom + '</h2><div class="col-md-4 inline-block"><button class="float-right" onclick="add_table(\''+division.id_division+'\')">Ajouter une équipe</button></div></div>');
            for (var i = 0; i < localStorage.length; i++){ //Boucle a travers les équipes générées
                var team = localStorage.key(i);
                if(division.id_division == team[4]) //Si la division correspond à la division de l'équipe
                {
                    if(team.indexOf("reserve") !== -1)
                    {
                        var equipe = [];
                        var result = JSON.parse(localStorage.getItem(team));
                        result.forEach(function(joueur)
                        {
                            joueur = joueur.splice(5,2);
                        });
                        equipe.push.apply(equipe,result);
                        table_reserve.rows.add(equipe).draw();
                    }
                    else
                    {
                        var css = ((i%2) == 0)? " float-left" : "";
                        var id_table = "table_team" + team.substr(4, 2);
                        var $table = $( '<div class="col-md-5 inline-table'+css+'"><h2>' + team[5] + '</h2><table id="'+id_table+'" class="table table-striped table-bordered" cellspacing="0" width="100%"><thead><tr><th>N°</th><th>Nom</th><th>Prenom</th><th>Class</th><th>Action</th></tr></thead><tbody></tbody></table><div class="col-md-8"><button class="float-left" onclick="select_joueur(\''+id_table+'\')">Ajouter un joueur</button><button class="float-right clear-fix" onclick="delete_table(\''+id_table+'\')">Supprimer l\'équipe</button></div></div>' );
                        $("#div" + team.charAt(4)).append($table);
                        var equipe = [];
                        var result = JSON.parse(localStorage.getItem(team));
                        result.forEach(function(joueur)
                        {
                            joueur = joueur.splice(4,3);
                        });
                        equipe.push.apply(equipe,result);
                        //datatables
                        table = $('#table_team' + team.substr(4, 2)).DataTable({
                            "columnDefs": [
                                {
                                    "data": null,
                                    "defaultContent": "<input type='button' value='Retirer'>",
                                    "targets": 4
                                }
                            ],
                            "bFilter": false,
                            "paging":   false,
                            "ordering": false,
                            "info":     false,
                            "responsive": true,
                            // Load data for the table's content from an Ajax source
                            data: equipe
                        });
                        $('#'+id_table+' tbody').on( 'click', 'input[type="button"]', function () {
                            var table = $(this).parents('table').DataTable();
                            var row = table.row( $(this).parents('tr') );
                            var rowNode = row.node();
                            row.remove();
                            table_reserve.row.add( rowNode ).draw();
                        });
                    }
                }    
            }
        });
    });
}

function show_reserve() 
{
    $("#alert-modal").hide();
    $("#reserve-modal").modal(); 
}

function nextChar(c) {
    return String.fromCharCode(c.charCodeAt(0) + 1);
}

function get_divisions(handleData)
{
    $.ajax({
        url : "<?php echo site_url('Division/ajax_get')?>",
        type: "GET",
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function(data)
        {
            handleData(data);
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            return null;
        }
    });
}

function get_equipes(division, handleData)
{
    $.ajax({
        url : "<?php echo site_url('Equipe/ajax_get_by_division/')?>" + division,
        type: "GET",
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function(data)
        {
            handleData(data);
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            return null;
        }
    });
}


function get_interclubs(handleData)
{
    $.ajax({
        url : "<?php echo site_url('Home/ajax_interclub')?>",
        type: "POST",
        contentType: false,
        processData: false,
        dataType: "JSON",
        success: function(data)
        {
            handleData(data);
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            alert('Error adding / update data');
        }
    });
}

function hide()
{
    $("#modal_table_effectif").DataTable().destroy();
    $("#modal_table_reserve").DataTable().destroy();
    $('#modal_form').modal('hide');
}


</script>
 