import ChoiceTree from "../../../../admin-dev/themes/new-theme/js/components/form/choice-tree";
import ChoiceTable from "../../../../admin-dev/themes/new-theme/js/components/choice-table"

var choiceTree = new ChoiceTree('.js-choice-tree-container');
choiceTree.enableAutoCheckChildren();

new ChoiceTable()