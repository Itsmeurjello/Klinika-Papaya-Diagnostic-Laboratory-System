<?php
// add_patient.php

include 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birth_date = $_POST['birth_date'];

    // Insert using prepared statement (secure)
    $stmt = $conn->prepare("INSERT INTO patients (full_name, gender, age, birth_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $full_name, $gender, $age, $birth_date);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
}
?>
<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.querySelector("#addModal form").addEventListener("submit", function(event) {
    event.preventDefault();

    const form = this;
    const formData = new FormData(form);

    fetch('add_patient.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        if (result.trim() === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Patient added successfully.',
                timer: 1500,
                showConfirmButton: false
            });

            // Optionally reset form and close modal
            form.reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
            modal.hide();

            // You can also manually reload the table if needed
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to add patient.'
            });
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An unexpected error occurred.'
        });
    });
});
</script>
