import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['list'];
    static values = { attributes: Array, labels: Object };

    add() {
        const index = this.listTarget.querySelectorAll(':scope > .row').length;
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2';
        const attribute = this.attributesValue[0];
        row.append(this.column(this.attributeSelect(index, attribute), 'col-md-4'));
        row.append(this.column(this.operatorSelect(index, attribute), 'col-md-3'));
        row.append(this.column(this.valueField(attribute, 'rules[' + index + '][value]'), 'col-md-4'));
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-outline-secondary';
        remove.textContent = '×';
        remove.dataset.action = 'access-rules#remove';
        row.append(this.column(remove, 'col-md-1'));
        this.listTarget.appendChild(row);
    }

    remove(event) {
        event.target.closest('.row')?.remove();
    }

    refresh(event) {
        const select = event.target;
        const row = select.closest('.row');
        const attribute = this.attributesValue.find((item) => String(item.id) === select.value);
        const operator = row.querySelector('[data-role="operator"]');
        const selected = operator.value;
        operator.innerHTML = '';
        this.operators(attribute ? attribute.type : '').forEach((name) => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = this.labelsValue[name] || name;
            option.selected = name === selected;
            operator.appendChild(option);
        });
        const holder = row.children[2];
        const current = holder.querySelector('[data-role="value"]');
        const name = current ? current.name : select.name.replace('[attribute]', '[value]');
        holder.innerHTML = '';
        holder.appendChild(this.valueField(attribute, name));
    }

    column(node, className) {
        const column = document.createElement('div');
        column.className = className;
        column.appendChild(node);
        return column;
    }

    attributeSelect(index, selected) {
        const select = document.createElement('select');
        select.className = 'form-select';
        select.name = 'rules[' + index + '][attribute]';
        select.dataset.action = 'change->access-rules#refresh';
        this.attributesValue.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            option.selected = selected && item.id === selected.id;
            select.appendChild(option);
        });
        return select;
    }

    operatorSelect(index, attribute) {
        const select = document.createElement('select');
        select.className = 'form-select';
        select.name = 'rules[' + index + '][operator]';
        select.dataset.role = 'operator';
        this.operators(attribute ? attribute.type : '').forEach((name) => {
            const option = document.createElement('option');
            option.value = name;
            option.textContent = this.labelsValue[name] || name;
            select.appendChild(option);
        });
        return select;
    }

    valueField(attribute, name) {
        if (attribute && attribute.type === 'one_of_many') {
            const select = document.createElement('select');
            select.className = 'form-select';
            select.name = name;
            select.dataset.role = 'value';
            (attribute.options || []).forEach((option) => {
                const node = document.createElement('option');
                node.value = option.id;
                node.textContent = option.label;
                select.appendChild(node);
            });
            return select;
        }
        const input = document.createElement('input');
        input.className = 'form-control';
        input.name = name;
        input.dataset.role = 'value';
        if (attribute && attribute.type === 'numeric') {
            input.type = 'number';
            input.step = '0.0001';
        } else if (attribute && (attribute.type === 'date' || attribute.type === 'period')) {
            input.type = 'date';
        } else if (attribute && attribute.type === 'boolean') {
            input.type = 'hidden';
        }
        return input;
    }

    operators(type) {
        const map = {
            string: ['eq', 'neq', 'contains'],
            text: ['contains'],
            numeric: ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'],
            date: ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'],
            period: ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'],
            boolean: ['checked', 'unchecked'],
            one_of_many: ['eq', 'neq'],
        };
        return map[type] || [];
    }
}
