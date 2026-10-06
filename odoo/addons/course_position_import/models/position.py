from odoo import fields, models

class CoursePosition(models.Model):
    _name = 'course.position'
    _description = 'Imported Position'

    name = fields.Char(string='Position Title', required=True)
    external_id = fields.Integer(string='External ID')
    company = fields.Char(string='Company')
    level = fields.Char(string='Level')
    cv_count = fields.Integer(string='CV Count')
    token = fields.Char(string='API Token', required=True)
    api_url = fields.Char(string='API URL')
    imported_at = fields.Datetime(string='Imported At')
    attribute_line_ids = fields.One2many('course.position.attribute.line', 'position_id', string='Attributes')

class CoursePositionAttributeLine(models.Model):
    _name = 'course.position.attribute.line'
    _description = 'Position Attribute Aggregation'

    position_id = fields.Many2one('course.position', string='Position', required=True, ondelete='cascade')
    name = fields.Char(string='Attribute Title')
    code = fields.Char(string='Code')
    type = fields.Char(string='Type')
    category = fields.Char(string='Category')
    count = fields.Integer(string='Values Count')
    aggregated = fields.Text(string='Aggregated JSON')
    display_value = fields.Char(string='Aggregated', compute='_compute_display')

    def _compute_display(self):
        import json
        for r in self:
            try:
                data = json.loads(r.aggregated or '{}')
            except Exception:
                data = {}
            if not data or data.get('count', 0) == 0:
                r.display_value = '—'
                continue
            if 'avg' in data:
                r.display_value = f"avg {data.get('avg')} min {data.get('min')} max {data.get('max')} (n={data.get('count')})"
            elif 'popular' in data:
                pop = data.get('popular') or []
                r.display_value = ', '.join(f"{p['value']}×{p['count']}" for p in pop[:3]) or f"n={data.get('count')}"
            elif 'options' in data:
                opts = data.get('options') or []
                r.display_value = ', '.join(f"{o['value']}×{o['count']}" for o in opts[:3])
            elif 'true' in data:
                r.display_value = f"true {data.get('true')} false {data.get('false')}"
            else:
                r.display_value = str(data)
