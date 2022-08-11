const formDiff = document.getElementById('form-diff')

formDiff.addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch("textDiff.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(json => {
            document.querySelector('.step-2').style.display = 'block'
            document.querySelector('.diff-result').innerHTML = json.text[1]
        });
});
