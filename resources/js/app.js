const root = document.documentElement;
const storedTheme = localStorage.getItem('tebbi-theme');
if (storedTheme === 'dark' || storedTheme === 'light') {
    root.dataset.theme = storedTheme;
}

document.querySelector('.theme-toggle')?.addEventListener('click', () => {
    const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = nextTheme;
    localStorage.setItem('tebbi-theme', nextTheme);
});

document.querySelector('.mobile-menu')?.addEventListener('click', () => {
    document.querySelector('.main-nav')?.classList.toggle('open');
});

const accountType = document.querySelector('#account-type');
const doctorFields = document.querySelector('#doctor-fields');
const updateDoctorFields = () => {
    if (!accountType || !doctorFields) return;
    doctorFields.hidden = accountType.value !== 'doctor';
    doctorFields.querySelectorAll('input:not([type="file"]), textarea').forEach((field) => {
        field.required = accountType.value === 'doctor';
    });
};
accountType?.addEventListener('change', updateDoctorFields);
updateDoctorFields();

document.querySelectorAll('input[name="for_someone_else"]').forEach((choice) => {
    choice.addEventListener('change', () => {
        const fields = document.querySelector('.other-person-fields');
        if (!fields) return;
        const showFields = document.querySelector('input[name="for_someone_else"]:checked')?.value === '1';
        fields.hidden = !showFields;
        fields.querySelectorAll('input').forEach((field) => { field.required = showFields; });
    });
});
