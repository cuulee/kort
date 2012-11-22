Ext.define('Kort.model.Reward', {
    extend: 'Ext.data.Model',
    config: {
		idProperty: 'id',
		
        fields: [
			{ name: 'id', type: 'auto' },
			{ name: 'koins', type: 'string' },
            { name: 'badges', type: 'array' }
        ],
        
        hasMany: { model: 'Badge', name: 'badges' }
    }
});