(function($) {
	$(document).ready(function() {
		var _media = wp.media

		$(document.body).on('click', '.percolate-images-activate', function(e) {
			e.preventDefault()

			if(!_media.frames.percolate) {
				_media.frames.percolate = wp.media.editor.open(wpActiveEditor, {
					state: 'percolate-images',
					frame: 'post'
				})
			} else {
				_media.frames.percolate.open(wpActiveEditor)
			}
		})
	})
})(jQuery)
