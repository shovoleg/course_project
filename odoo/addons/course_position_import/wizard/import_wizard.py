from odoo import fields, models
from odoo.exceptions import UserError
import json
import logging
_logger = logging.getLogger(__name__)

class CoursePositionImportWizard(models.TransientModel):
    _name = 'course.position.import.wizard'
    _description = 'Import Position by API Token'

    token = fields.Char(string='API Token', required=True)
    base_url = fields.Char(string='Course API Base URL', default='https://courseproject.odotibmebel.synology.me', required=True)

    def action_import(self):
        self.ensure_one()
        token = self.token.strip()
        base = self.base_url.strip().rstrip('/')
        url = f"{base}/api/external/position/{token}"
        try:
            import requests
            r = requests.get(url, timeout=15)
            if r.status_code != 200:
                raise UserError(f"API error {r.status_code}: {r.text[:500]}")
            data = r.json()
        except UserError:
            raise
        except Exception as e:
            raise UserError(f"Request failed: {e}")
        pos = data.get('position') or {}
        attrs = data.get('attributes') or []
        if not pos.get('id'):
            raise UserError('Invalid response: no position')
        Position = self.env['course.position']
        existing = Position.search([('token', '=', token)], limit=1)
        vals = {
            'name': pos.get('title') or f"Position {pos.get('id')}",
            'external_id': pos.get('id'),
            'company': pos.get('company'),
            'level': pos.get('level'),
            'cv_count': pos.get('cvCount') or 0,
            'token': token,
            'api_url': url,
            'imported_at': fields.Datetime.now(),
        }
        if existing:
            existing.write(vals)
            # clear old lines
            existing.attribute_line_ids.unlink()
            position = existing
        else:
            position = Position.create(vals)
        Line = self.env['course.position.attribute.line']
        for a in attrs:
            Line.create({
                'position_id': position.id,
                'name': a.get('title'),
                'code': a.get('code'),
                'type': a.get('type'),
                'category': a.get('category'),
                'count': (a.get('aggregated') or {}).get('count', 0) if isinstance(a.get('aggregated'), dict) else 0,
                'aggregated': json.dumps(a.get('aggregated') or {}, ensure_ascii=False),
            })
        return {
            'type': 'ir.actions.act_window',
            'res_model': 'course.position',
            'view_mode': 'form',
            'res_id': position.id,
            'target': 'current',
        }
