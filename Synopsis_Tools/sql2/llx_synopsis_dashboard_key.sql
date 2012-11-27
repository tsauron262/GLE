ALTER TABLE `llx_Synopsis_Dashboard`
  ADD CONSTRAINT `llx_Synopsis_Dashboard_ibfk_1` FOREIGN KEY (`user_refid`) REFERENCES `llx_user` (`rowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `llx_Synopsis_Dashboard_ibfk_2` FOREIGN KEY (`dash_type_refid`) REFERENCES `llx_Synopsis_Dashboard_page` (`id`) ON DELETE CASCADE;