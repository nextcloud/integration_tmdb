/**
 * @copyright Copyright (c) 2023 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import { registerWidget } from '@nextcloud/vue/components/NcRichText'
import { linkTo } from '@nextcloud/router'
import { getCSPNonce } from '@nextcloud/auth'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('integration_tmdb', 'js/') // eslint-disable-line

registerWidget('integration_tmdb_movie', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: TmdbMovieReferenceWidget } = await import(/* webpackChunkName: "reference-tmdbwidget-lazy" */'./components/TmdbMovieReferenceWidget.vue')

	const app = createApp(
		TmdbMovieReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })

registerWidget('integration_tmdb_person', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: TmdbPersonReferenceWidget } = await import(/* webpackChunkName: "reference-tmdbwidget-lazy" */'./components/TmdbPersonReferenceWidget.vue')

	const app = createApp(
		TmdbPersonReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })

registerWidget('integration_tmdb_tv', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: TmdbTvReferenceWidget } = await import(/* webpackChunkName: "reference-tmdbwidget-lazy" */'./components/TmdbTvReferenceWidget.vue')

	const app = createApp(
		TmdbTvReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })
