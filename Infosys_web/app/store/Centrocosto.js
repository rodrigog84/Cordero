Ext.define('Infosys_web.store.Centrocosto', {
    extend: 'Ext.data.Store',
    model: 'Infosys_web.model.centrocosto',
    autoLoad: true,
    pageSize: 14,
    
    proxy: {
        type: 'ajax',
         actionMethods:  {
            read: 'POST'
         },
        api: {
            create: preurl + 'centrocosto/save', 
            read: preurl + 'centrocosto/getAll',
            update: preurl + 'centrocosto/update'
            //destroy: 'php/deletaContacto.php'
        },
        reader: {
            type: 'json',
            root: 'data',
            successProperty: 'success'
        },
        writer: {
            type: 'json',
            writeAllFields: true,
            encode: true,
            root: 'data'
        }
    }
});