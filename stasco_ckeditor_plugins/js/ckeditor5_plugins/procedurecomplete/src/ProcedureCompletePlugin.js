import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

export default class ProcedureCompletePlugin extends Plugin {
    init() {
        const editor = this.editor;

        // Dodaj komendę
        editor.commands.add('insertProcedureComplete', {
            execute: () => {
                const viewFragment = editor.data.processor.toView('<a stasco_procedure_complete="true" href="" class="procedure-complete__button" target="_self">Procedure complete</a>');
                const modelFragment = editor.data.toModel(viewFragment);
                editor.model.insertContent(modelFragment);
            }
        });

        // Dodaj przycisk do interfejsu użytkownika
        editor.ui.componentFactory.add('procedureComplete', locale => {
            const view = new ButtonView(locale);
            view.set({
                label: 'Procedure Complete',
                tooltip: true
            });

            // Callback wywoływany po kliknięciu przycisku
            view.on('execute', () => {
                editor.execute('insertProcedureComplete');
                editor.editing.view.focus();
            });

            return view;
        });
    }
}
