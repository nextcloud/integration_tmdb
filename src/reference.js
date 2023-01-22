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

import { registerWidget } from '@nextcloud/vue-richtext'
import './bootstrap.js'
import Vue from 'vue'
import TmdbMovieReferenceWidget from './components/TmdbMovieReferenceWidget.vue'
import TmdbPersonReferenceWidget from './components/TmdbPersonReferenceWidget.vue'
import TmdbTvReferenceWidget from './components/TmdbTvReferenceWidget.vue'

registerWidget('integration_tmdb_movie', (el, { richObjectType, richObject, accessible }) => {
	const Widget = Vue.extend(TmdbMovieReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})

registerWidget('integration_tmdb_person', (el, { richObjectType, richObject, accessible }) => {
	const Widget = Vue.extend(TmdbPersonReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})

registerWidget('integration_tmdb_tv', (el, { richObjectType, richObject, accessible }) => {
	const Widget = Vue.extend(TmdbTvReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})
