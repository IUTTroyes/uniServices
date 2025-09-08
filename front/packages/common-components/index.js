import LayoutComponent from './components/layout/AppLayout.vue';
import LoginComponent from './pages/auth/Login.vue';
import FooterComponent from './components/layout/AppFooter.vue';
import TopbarComponent from './components/layout/AppTopbar.vue';
import SimpleSkeleton from '@components/loader/SimpleSkeleton.vue';
import ArticleSkeleton from '@components/loader/ArticleSkeleton.vue';
import ListSkeleton from '@components/loader/ListSkeleton.vue';
import CardSkeleton from "@components/loader/CardSkeleton.vue";
import ApcCompetenceBadge from "@components/Apc/ApcCompetenceBadge.vue";
import ApcAcBadge from "@components/Apc/ApcAcBadge.vue";
import ErrorView from '@components/components/ErrorView.vue';
import PhotoUser from '@components/components/PhotoUser.vue';

// Form validation components
import FormValidator from './components/Forms/FormValidator.vue';
import ValidatedInput from './components/Forms/ValidatedInput.vue';
import ExampleValidatedForm from './components/Forms/ExampleValidatedForm.vue';
import { validationRules, validateField } from './utils/formValidation';

export {
  LayoutComponent,
  LoginComponent,
  FooterComponent,
  TopbarComponent,
  SimpleSkeleton,
  ArticleSkeleton,
  ListSkeleton,
  CardSkeleton,
  ApcCompetenceBadge,
  ApcAcBadge,
  ErrorView,
  PhotoUser,

  // Form validation exports
  FormValidator,
  ValidatedInput,
  ExampleValidatedForm,
  validationRules,
  validateField,
};
