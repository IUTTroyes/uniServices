import {ref} from 'vue'

const storedValue = localStorage.getItem('showGlobalMessage') === 'true';
const showMessage = ref(storedValue);

const showMessageChecked = () => {
    showMessage.value = !showMessage.value;
    localStorage.setItem('showGlobalMessage', showMessage.value.toString());

}


export {showMessage,showMessageChecked};