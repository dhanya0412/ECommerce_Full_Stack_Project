let currentQuestion = {};
let selectedAnswer = null;

$('#startTrivia').click(() => {
  $.post('ajaxhandler/triviaAjax.php', {
    action: 'fetch_question',
    theme: $('#theme').val(),
    difficulty: $('#difficulty').val()
  }, function (data) {
    if (data.status === 'played') {
      $('#result').html('<div class="alert alert-warning">You already played today!</div>');
    } else if (data.status === 'ok') {
      currentQuestion = data.question;
      $('#questionText').text(currentQuestion.question_text);
      $('#options').empty();
      ['A','B','C','D'].forEach(opt => {
        const text = currentQuestion['option_' + opt.toLowerCase()];
        $('#options').append(
          `<div class='form-check'>
            <input class='form-check-input' type='radio' name='option' value='${opt}' id='opt${opt}'>
            <label class='form-check-label' for='opt${opt}'>${text}</label>
          </div>`
        );
      });
      $('#questionBox').show();
    }
  }, 'json');
});

$('#submitAnswer').click(() => {
  selectedAnswer = $('input[name="option"]:checked').val();
  if (!selectedAnswer) {
    alert("Please select an option");
    return;
  }

  $.post('ajaxhandler/triviaAjax.php', {
    action: 'submit_answer',
    question_id: currentQuestion.question_id,
    answer: selectedAnswer
  }, function (data) {
    if (data.correct) {
      $('#result').html(`<div class='alert alert-success'>🎉 Correct! Use code <strong>${data.code}</strong> to get ${data.discount}% off.</div>`);
    } else {
      $('#result').html('<div class="alert alert-danger">❌ Incorrect! Better luck tomorrow!</div>');
    }
    $('#questionBox').hide();
  }, 'json');
});
