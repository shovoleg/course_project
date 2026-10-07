{
    'name': 'Course Position Import',
    'version': '1.0.0',
    'summary': 'Import positions and aggregated results from Course Project via API token',
    'category': 'Tools',
    'author': 'Course Project',
    'license': 'LGPL-3',
    'depends': ['base'],
    'data': [
        'security/ir.model.access.csv',
        'views/position_views.xml',
        'wizard/import_wizard_views.xml',
    ],
    'installable': True,
    'application': True,
}
