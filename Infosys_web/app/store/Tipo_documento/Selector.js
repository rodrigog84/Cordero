Ext.define('Infosys_web.store.Tipo_documento.Selector', {
    extend: 'Ext.data.Store',
	fields: ['id', 'nombre'],
    data : [
        {"id":"101", "nombre":"FACTURA ELECTRONICA"},
        {"id":"103", "nombre":"FACTURA EXENTA ELECTRONICA"},        
        {"id":"2", "nombre":"BOLETA"},
     	{"id":"105", "nombre":"GUIA DE DESPACHO ELECTRONICA"},    
    ]
});